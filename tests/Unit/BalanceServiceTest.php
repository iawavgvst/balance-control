<?php

namespace Tests\Unit;

use Exception;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use App\Http\Services\BalanceService;
use App\Models\Balance;
use App\Models\User;
use Throwable;

class BalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BalanceService $balanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->balanceService = new BalanceService();
    }

    /**
     * Проверяет получение баланса существующего пользователя:
     * Создает пользователя с балансом 150.75 и проверяет, что сервис возвращает правильную сумму
     */
    #[Test]
    public function it_gets_balance()
    {
        $user = User::factory()->create();
        Balance::create(['user_id' => $user->id, 'amount' => 150.75]);

        $result = $this->balanceService->getBalance($user->id);

        $this->assertEquals(['user_id' => $user->id, 'balance' => 150.75], $result);
    }

    /**
     * Проверяет возврат нулевого баланса для пользователя без записи в таблице balances:
     * Создает пользователя без баланса и проверяет, что сервис возвращает 0.00
     */
    #[Test]
    public function it_returns_zero_for_user_without_balance()
    {
        $user = User::factory()->create();

        $result = $this->balanceService->getBalance($user->id);

        $this->assertEquals(['user_id' => $user->id, 'balance' => 0.00], $result);
    }

    /**
     *  Проверяет пополнение баланса у существующего пользователя:
     *  Создает пользователя с начальным балансом 100.00, пополняет на 50.25
     *  Проверяет новый баланс 150.25 и запись в базе данных
     *
     * @throws Throwable
     */
    #[Test]
    public function it_deposits_funds()
    {
        $user = User::factory()->create();
        Balance::create(['user_id' => $user->id, 'amount' => 100.00]);

        $result = $this->balanceService->deposit($user->id, 50.25);

        $this->assertEquals(150.25, $result['new_balance']);
        $this->assertDatabaseHas('balances', ['user_id' => $user->id, 'amount' => 150.25]);
    }

    /**
     *  Проверяет списание средств с баланса:
     *  Создает пользователя с балансом 200.00 и списывает 75.50
     *  Проверяет новый баланс 124.50 и обновление в базе данных
     *
     * @throws Throwable
     */
    #[Test]
    public function it_withdraws_funds()
    {
        $user = User::factory()->create();
        Balance::create(['user_id' => $user->id, 'amount' => 200.00]);

        $result = $this->balanceService->withdraw($user->id, 75.50);

        $this->assertEquals(124.50, $result['new_balance']);
        $this->assertDatabaseHas('balances', ['user_id' => $user->id, 'amount' => 124.50]);
    }

    /**
     *  Проверяет исключение при попытке списать больше средств, чем есть на балансе:
     *  Создает пользователя с балансом 50.00 и пытается списать 100.00
     *  Ожидает исключение с сообщением о недостатке средств
     *
     * @throws Exception
     * @throws Throwable
     */
    #[Test]
    public function it_throws_exception_when_withdrawing_insufficient_funds()
    {
        $user = User::factory()->create();
        Balance::create(['user_id' => $user->id, 'amount' => 50.00]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Недостаточно средств на балансе.');

        $this->balanceService->withdraw($user->id, 100.00);
    }

    /**
     *  Проверяет перевод средств между двумя пользователями:
     *  Создает отправителя с балансом 300.00 и получателя с балансом 100.00
     *  Переводит 150.75, проверяет новые балансы 149.25 и 250.75
     *  Проверяет обновление записей в базе данных
     *
     * @throws Throwable
     */
    #[Test]
    public function it_transfers_funds()
    {
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        Balance::create(['user_id' => $fromUser->id, 'amount' => 300.00]);
        Balance::create(['user_id' => $toUser->id, 'amount' => 100.00]);

        $result = $this->balanceService->transfer($fromUser->id, $toUser->id, 150.75);

        $this->assertEquals(149.25, $result['balance_from_user_id']);
        $this->assertEquals(250.75, $result['balance_to_user_id']);

        $this->assertDatabaseHas('balances', ['user_id' => $fromUser->id, 'amount' => 149.25]);
        $this->assertDatabaseHas('balances', ['user_id' => $toUser->id, 'amount' => 250.75]);
    }

    /**
     *  Проверяет исключение при попытке перевода средств самому себе:
     *  Создает пользователя с балансом 200.00 пытается перевести 100.00 себе же
     *  Ожидает исключение с сообщением о невозможности перевода самому себе
     *
     * @throws Exception
     * @throws Throwable
     */
    #[Test]
    public function it_throws_exception_when_transferring_to_same_user()
    {
        $user = User::factory()->create();
        Balance::create(['user_id' => $user->id, 'amount' => 200.00]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Нельзя переводить средства самому себе.');

        $this->balanceService->transfer($user->id, $user->id, 100.00);
    }

    /**
     *  Проверяет исключение при попытке перевода больше средств, чем есть на балансе:
     *  Создает отправителя с балансом 50.00 и получателя
     *  Пытается перевести 100.00, ожидает исключение с сообщением о недостатке средств
     *
     * @throws Exception
     * @throws Throwable
     */
    #[Test]
    public function it_throws_exception_when_transferring_insufficient_funds()
    {
        $fromUser = User::factory()->create();
        $toUser = User::factory()->create();

        Balance::create(['user_id' => $fromUser->id, 'amount' => 50.00]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Недостаточно средств для перевода.');

        $this->balanceService->transfer($fromUser->id, $toUser->id, 100.00);
    }
}
