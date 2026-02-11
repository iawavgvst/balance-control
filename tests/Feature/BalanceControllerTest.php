<?php

namespace Tests\Feature;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Balance;

class BalanceControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Проверяет успешное получение баланса пользователя:
     * Создает пользователя с балансом 150.75, отправляет GET-запрос
     * Проверяет статус 200 и правильную структуру JSON-ответа
     */
    #[Test]
    public function it_returns_balance()
    {
        $user = User::factory()->create();
        Balance::create(['user_id' => $user->id, 'amount' => 150.75]);

        $response = $this->getJson("/api/balance/$user->id");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'balance' => 150.75,
                ]
            ]);
    }

    /**
     * Проверяет возврат нулевого баланса для нового пользователя:
     * Создает пользователя без баланса, отправляет GET-запрос
     * Проверяет, что API возвращает balance 0.00
     */
    #[Test]
    public function it_returns_zero_balance_for_new_user()
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/balance/$user->id");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'balance' => 0.00,
                ]
            ]);
    }

    /**
     * Проверяет обработку несуществующего пользователя:
     * Отправляет GET-запрос с несуществующим ID пользователя 13
     * Проверяет статус 404 (Not Found) и сообщение об ошибке
     */
    #[Test]
    public function it_returns_404_when_user_not_found()
    {
        $response = $this->getJson("/api/balance/13");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => [
                    'error' => 'Пользователь не найден.'
                ]
            ]);
    }

    /**
     * Проверяет успешное пополнение баланса:
     * Создает пользователя с балансом 100.00, отправляет POST-запрос на пополнение на 50.25
     * Проверяет статус 200 и новый баланс
     */
    #[Test]
    public function it_deposits_funds()
    {
        $user = User::factory()->create();
        Balance::create(['user_id' => $user->id, 'amount' => 100.00]);

        $response = $this->postJson('/api/deposit', [
            'user_id' => $user->id,
            'amount' => 50.25,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'new_balance' => 150.25,
                ]
            ]);
    }

    /**
     * Проверяет пополнение баланса для нового пользователя:
     * Создает пользователя без баланса, отправляет POST-запрос на пополнение на 100.00
     * Проверяет создание записи баланса с указанной суммой
     */
    #[Test]
    public function it_deposits_funds_for_new_user()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/deposit', [
            'user_id' => $user->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'new_balance' => 100.00,
                ]
            ]);
    }

    /**
     * Проверяет успешное списание средств:
     * Создает пользователя с балансом 200.00, отправляет POST-запрос на списание с баланса 75.50
     * Проверяет статус 200 и новый баланс
     */
    #[Test]
    public function it_withdraws_funds()
    {
        $user = User::factory()->create();
        Balance::create(['user_id' => $user->id, 'amount' => 200.00]);

        $response = $this->postJson('/api/withdraw', [
            'user_id' => $user->id,
            'amount' => 75.50,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'new_balance' => 124.50,
                ]
            ]);
    }

    /**
     * Проверяет ошибку при попытке списать больше средств, чем есть на балансе:
     * Создает пользователя с балансом 50.00, отправляет POST-запрос на списание 100.00
     * Проверяет статус 409 (Conflict) и сообщение об ошибке
     */
    #[Test]
    public function it_returns_409_when_withdrawing_insufficient_funds()
    {
        $user = User::factory()->create();
        Balance::create(['user_id' => $user->id, 'amount' => 50.00]);

        $response = $this->postJson('/api/withdraw', [
            'user_id' => $user->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => [
                    'error' => 'Недостаточно средств на балансе.'
                ]
            ]);
    }

    /**
     * Проверяет ошибку при попытке списать средства у пользователя без баланса:
     * Создает пользователя без записи баланса, отправляет POST-запрос на списание
     * Проверяет статус 409 и сообщение об ошибке
     */
    #[Test]
    public function it_returns_409_when_withdrawing_from_user_without_balance()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/withdraw', [
            'user_id' => $user->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => [
                    'error' => 'Недостаточно средств на балансе.'
                ]
            ]);
    }

    /**
     * Проверяет успешный перевод средств между пользователями:
     * Создает отправителя с балансом 300.00 и получателя с балансом 100.00
     * Отправляет POST-запрос на перевод 150.75
     * Проверяет новые балансы 149.25 и 250.75
     */
    #[Test]
    public function it_transfers_funds()
    {
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        Balance::create(['user_id' => $fromUser->id, 'amount' => 300.00]);
        Balance::create(['user_id' => $toUser->id, 'amount' => 100.00]);

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'amount' => 150.75,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'from_user_id' => $fromUser->id,
                    'to_user_id' => $toUser->id,
                    'balance_from_user_id' => 149.25,
                    'balance_to_user_id' => 250.75,
                ]
            ]);
    }

    /**
     * Проверяет перевод средств пользователю, у которого нет записи баланса:
     * Создает отправителя с балансом 300.00 и получателя без баланса
     * Отправляет POST-запрос на перевод 150.75
     * Проверяет создание записи баланса для получателя
     */
    #[Test]
    public function it_transfers_funds_to_new_user()
    {
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        Balance::create(['user_id' => $fromUser->id, 'amount' => 300.00]);

        $response = $this->postJson('/api/transfer', [
            'from_user_id' => $fromUser->id,
            'to_user_id' => $toUser->id,
            'amount' => 150.75,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'from_user_id' => $fromUser->id,
                    'to_user_id' => $toUser->id,
                    'balance_from_user_id' => 149.25,
                    'balance_to_user_id' => 150.75,
                ]
            ]);
    }

    /**
     * Проверяет валидацию входных данных для всех API-эндпоинтов:
     * Отправляет POST-запросы с пустыми данными на /api/deposit, /api/withdraw, /api/transfer
     * Проверяет статус 422 (Unprocessable Entity) для каждого запроса
     */
    #[Test]
    public function it_returns_validation_errors()
    {
        $response = $this->postJson('/api/deposit', []);
        $response->assertStatus(422);

        $response = $this->postJson('/api/withdraw', []);
        $response->assertStatus(422);

        $response = $this->postJson('/api/transfer', []);
        $response->assertStatus(422);
    }
}
