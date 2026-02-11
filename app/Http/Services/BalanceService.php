<?php

namespace App\Http\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Throwable;

class BalanceService
{
    /**
     * Получение баланса
     *
     * @param int $userId
     * @return array
     * @throws ModelNotFoundException
     */
    public function getBalance(int $userId): array
    {
        $user = User::findOrFail($userId);
        $balance = $user->balance;

        return [
            'user_id' => $user->id,
            'balance' => $balance ? (float) $balance->amount : 0.00
        ];
    }

    /**
     * Начисление средств (пополнение баланса)
     *
     * @param int $userId
     * @param float $amount
     * @param string|null $comment
     * @return array
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    public function deposit(int $userId, float $amount, ?string $comment = null): array
    {
        return DB::transaction(function () use ($userId, $amount, $comment) {
            $user = User::findOrFail($userId);

            $balance = $user->balance()->firstOrCreate(
                ['user_id' => $userId],
                ['amount' => 0]
            );

            $balance->increment('amount', $amount);

            $transaction = Transaction::create([
                'user_id' => $userId,
                'type' => TransactionType::DEPOSIT,
                'amount' => $amount,
                'comment' => $comment,
            ]);

            return [
                'user_id' => $userId,
                'new_balance' => (float) $balance->amount,
                'transaction_id' => $transaction->id,
                'message' => 'Баланс успешно пополнен.'
            ];
        });
    }

    /**
     * Списание средства
     *
     * @param int $userId
     * @param float $amount
     * @param string|null $comment
     * @return array
     * @throws ModelNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function withdraw(int $userId, float $amount, ?string $comment = null): array
    {
        return DB::transaction(function () use ($userId, $amount, $comment) {
            $user = User::findOrFail($userId);

            $balance = $user->balance()->firstOrCreate(
                ['user_id' => $userId],
                ['amount' => 0]
            );

            if ((float) $balance->amount < $amount) {
                throw new Exception('Недостаточно средств на балансе.', 409);
            }

            $balance->decrement('amount', $amount);

            $transaction = Transaction::create([
                'user_id' => $userId,
                'type' => TransactionType::WITHDRAW,
                'amount' => $amount,
                'comment' => $comment,
            ]);

            return [
                'user_id' => $userId,
                'new_balance' => (float) $balance->amount,
                'transaction_id' => $transaction->id,
                'message' => 'Средства успешно списаны.'
            ];
        });
    }

    /**
     * Перевод средств между пользователями
     *
     * @param int $fromUserId
     * @param int $toUserId
     * @param float $amount
     * @param string|null $comment
     * @return array
     * @throws ModelNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function transfer(int $fromUserId, int $toUserId, float $amount, ?string $comment = null): array
    {
        return DB::transaction(function () use ($fromUserId, $toUserId, $amount, $comment) {
            $fromUser = User::findOrFail($fromUserId);
            $toUser = User::findOrFail($toUserId);

            if ($fromUserId === $toUserId) {
                throw new Exception('Нельзя переводить средства самому себе.', 400);
            }

            $fromBalance = $fromUser->balance()->firstOrCreate(
                ['user_id' => $fromUserId],
                ['amount' => 0]
            );

            if ((float) $fromBalance->amount < $amount) {
                throw new Exception('Недостаточно средств для перевода.', 409);
            }

            $toBalance = $toUser->balance()->firstOrCreate(
                ['user_id' => $toUserId],
                ['amount' => 0]
            );

            $fromBalance->decrement('amount', $amount);

            $toBalance->increment('amount', $amount);

            $outTransaction = Transaction::create([
                'user_id' => $fromUserId,
                'type' => TransactionType::TRANSFER_OUT,
                'amount' => $amount,
                'comment' => $comment,
                'related_user_id' => $toUserId,
            ]);

            $inTransaction = Transaction::create([
                'user_id' => $toUserId,
                'type' => TransactionType::TRANSFER_IN,
                'amount' => $amount,
                'comment' => $comment,
                'related_user_id' => $fromUserId,
            ]);

            return [
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'amount' => $amount,
                'balance_from_user_id' => (float) $fromBalance->amount,
                'balance_to_user_id' => (float) $toBalance->amount,
                'out_transaction_id' => $outTransaction->id,
                'in_transaction_id' => $inTransaction->id,
                'message' => 'Перевод успешно выполнен.'
            ];
        });
    }
}
