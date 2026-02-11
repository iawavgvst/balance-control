<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\BalanceService;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\TransferRequest;
use App\Http\Requests\WithdrawRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Throwable;
use Illuminate\Http\JsonResponse;

class BalanceController extends Controller
{
    /**
     * Сервис для работы с балансом
     *
     * @var BalanceService
     */
    protected BalanceService $balanceService;

    /**
     * Конструктор
     *
     * @param BalanceService $balanceService
     */
    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Получение баланса
     *
     * Метод GET: /api/balance/{user_id}
     *
     * @param int $userId
     * @return JsonResponse
     * @throws ModelNotFoundException
     */
    public function getBalance(int $userId): JsonResponse
    {
        try {
            if ($userId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => [
                        'error' => 'Идентификатор пользователя должен быть положительным числом.'
                    ]
                ], 400);
            }

            $data = $this->balanceService->getBalance($userId);

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => [
                    'error' => 'Пользователь не найден.'
                ]
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => [
                    'error' => 'Произошла ошибка при пополнении баланса.'
                ]
            ], 500);
        }
    }

    /**
     * Пополнение баланса
     *
     * Метод POST: /api/deposit
     *
     * @param DepositRequest $request
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws Throwable
     */
    public function deposit(DepositRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $result = $this->balanceService->deposit(
                $data['user_id'],
                $data['amount'],
                $data['comment'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => [
                    'error' => 'Пользователь не найден.'
                ]
            ], 404);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => [
                    'error' => 'Произошла ошибка при пополнении баланса.'
                ]
            ], 500);
        }
    }

    /**
     * Списание средств
     *
     * Метод POST: /api/withdraw
     *
     * @param WithdrawRequest $request
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $result = $this->balanceService->withdraw(
                $data['user_id'],
                $data['amount'],
                $data['comment'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ], 200);

        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => [
                    'error' => 'Пользователь не найден.'
                ]
            ], 404);

        } catch (Exception $e) {
            $statusCode = $e->getCode() === 409 ? 409 : 500;
            $message = $e->getCode() === 409
                ? $e->getMessage()
                : 'Произошла ошибка при списании средств.';

            return response()->json([
                'success' => false,
                'message' => ['error' => $message]
            ], $statusCode);
        }
    }

    /**
     * Перевод средств
     *
     * Метод POST: /api/transfer
     *
     * @param TransferRequest $request
     * @return JsonResponse
     * @throws ModelNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function transfer(TransferRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $result = $this->balanceService->transfer(
                $data['from_user_id'],
                $data['to_user_id'],
                $data['amount'],
                $data['comment'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $result
            ], 200);

        } catch (ModelNotFoundException) {
            $data = $request->validated();

            $fromUserId = $data['from_user_id'];
            $toUserId = $data['to_user_id'];

            $fromUserExists = User::where('id', $fromUserId)->exists();
            $toUserExists = User::where('id', $toUserId)->exists();

            if (!$fromUserExists) {
                $message = 'Пользователь-отправитель не найден.';
            } elseif (!$toUserExists) {
                $message = 'Пользователь-получатель не найден.';
            } else {
                $message = 'Пользователь не найден.';
            }

            return response()->json([
                'success' => false,
                'message' => [
                    'error' => $message
                ]
            ], 404);

        } catch (Exception $e) {
            $statusCode = $e->getCode() === 409 ? 409 : ($e->getCode() === 400 ? 400 : 500);
            $message = $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => [
                    'error' => $message
                ]
            ], $statusCode);
        }
    }
}
