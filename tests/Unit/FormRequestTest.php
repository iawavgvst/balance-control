<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\TransferRequest;
use App\Http\Requests\WithdrawRequest;

class FormRequestTest extends TestCase
{
    /**
     * Проверяет успешную валидацию корректных данных для пополнения баланса:
     * Создает валидные данные (user_id, amount, comment) и проверяет, что валидатор их принимает
     */
    #[Test]
    public function deposit_request_validates_correct_data()
    {
        $request = new DepositRequest();

        $validData = [
            'user_id' => 1,
            'amount' => 100.50,
            'comment' => 'Тестовый комментарий.',
        ];

        $validator = $this->app['validator']->make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Проверяет ошибку валидации при отсутствии user_id в запросе на пополнение:
     * Создает данные без user_id и проверяет, что валидатор возвращает ошибку для этого поля
     */
    #[Test]
    public function deposit_request_fails_without_user_id()
    {
        $request = new DepositRequest();

        $invalidData = [
            'amount' => 100.50,
            'comment' => 'Тест.',
        ];

        $validator = $this->app['validator']->make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('user_id'));
    }

    /**
     * Проверяет ошибку валидации при отрицательной сумме пополнения:
     * Создает данные с amount -10 и проверяет, что валидатор возвращает ошибку
     */
    #[Test]
    public function deposit_request_fails_with_negative_amount()
    {
        $request = new DepositRequest();

        $invalidData = [
            'user_id' => 1,
            'amount' => -10,
            'comment' => 'Тест.',
        ];

        $validator = $this->app['validator']->make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('amount'));
    }

    /**
     * Проверяет ошибку валидации при нулевой сумме пополнения:
     * Создает данные с amount 0 и проверяет, что валидатор возвращает ошибку
     */
    #[Test]
    public function deposit_request_fails_with_zero_amount()
    {
        $request = new DepositRequest();

        $invalidData = [
            'user_id' => 1,
            'amount' => 0,
            'comment' => 'Тест.',
        ];

        $validator = $this->app['validator']->make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('amount'));
    }

    /**
     * Проверяет ошибку валидации при слишком большой сумме пополнения:
     * Создает данные с amount 1 000 000 000 000.00 и проверяет, что валидатор возвращает ошибку
     */
    #[Test]
    public function deposit_request_fails_with_too_large_amount()
    {
        $request = new DepositRequest();

        $invalidData = [
            'user_id' => 1,
            'amount' => 1000000000000.00,
            'comment' => 'Тест',
        ];

        $validator = $this->app['validator']->make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('amount'));
    }

    /**
     * Проверяет успешную валидацию корректных данных для списания средств:
     * Создает валидные данные (user_id, amount, comment) и проверяет, что валидатор их принимает
     */
    #[Test]
    public function withdraw_request_validates_correct_data()
    {
        $request = new WithdrawRequest();

        $validData = [
            'user_id' => 1,
            'amount' => 50.25,
            'comment' => 'Тестовый комментарий.',
        ];

        $validator = $this->app['validator']->make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Проверяет ошибку валидации при строковом значении user_id в запросе на списание:
     * Создает данные, где user_id не является числом, и проверяет, что валидатор возвращает ошибку
     */
    #[Test]
    public function withdraw_request_fails_with_string_user_id()
    {
        $request = new WithdrawRequest();

        $invalidData = [
            'user_id' => 'не число',
            'amount' => 50,
            'comment' => 'Тест.',
        ];

        $validator = $this->app['validator']->make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('user_id'));
    }

    /**
     * Проверяет ошибку валидации при слишком длинном комментарии в запросе на списание:
     * Создает данные с комментарием длиной 256 символов и проверяет, что валидатор возвращает ошибку
     */
    #[Test]
    public function withdraw_request_fails_with_too_long_comment()
    {
        $request = new WithdrawRequest();

        $invalidData = [
            'user_id' => 1,
            'amount' => 50,
            'comment' => str_repeat('a', 256),
        ];

        $validator = $this->app['validator']->make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('comment'));
    }

    /**
     * Проверяет успешную валидацию корректных данных для перевода средств:
     * Создает валидные данные (from_user_id, to_user_id, amount, comment) и проверяет валидацию
     */
    #[Test]
    public function transfer_request_validates_correct_data()
    {
        $request = new TransferRequest();

        $validData = [
            'from_user_id' => 1,
            'to_user_id' => 2,
            'amount' => 75.50,
            'comment' => 'Тестовый перевод.',
        ];

        $validator = $this->app['validator']->make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Проверяет ошибку валидации при отсутствии to_user_id в запросе на перевод:
     * Создает данные без to_user_id и проверяет, что валидатор возвращает ошибку
     */
    #[Test]
    public function transfer_request_fails_without_to_user_id()
    {
        $request = new TransferRequest();

        $invalidData = [
            'from_user_id' => 1,
            'amount' => 75.50,
            'comment' => 'Тест.',
        ];

        $validator = $this->app['validator']->make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('to_user_id'));
    }

    /**
     * Проверяет ошибку валидации при слишком маленькой сумме перевода:
     * Создает данные с amount = 0.001 и проверяет, что валидатор возвращает ошибку
     */
    #[Test]
    public function transfer_request_fails_with_small_amount()
    {
        $request = new TransferRequest();

        $invalidData = [
            'from_user_id' => 1,
            'to_user_id' => 2,
            'amount' => 0.001,
            'comment' => 'Тест.',
        ];

        $validator = $this->app['validator']->make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('amount'));
    }

    /**
     * Проверяет работу кастомных сообщений об ошибках для DepositRequest:
     * Создает невалидные данные и проверяет, что возвращаются правильные кастомные сообщения
     */
    #[Test]
    public function deposit_request_custom_messages_work()
    {
        $request = new DepositRequest();

        $invalidData = [
            'amount' => -10,
        ];

        $validator = $this->app['validator']->make($invalidData, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();

        $this->assertEquals(
            'Идентификатор пользователя обязателен.',
            $errors->first('user_id')
        );

        $this->assertEquals(
            'Минимальная сумма пополнения составляет 0.01.',
            $errors->first('amount')
        );
    }

    /**
     * Проверяет работу кастомных сообщений об ошибках для WithdrawRequest:
     * Создает невалидные данные и проверяет, что возвращаются правильные кастомные сообщения
     */
    #[Test]
    public function withdraw_request_custom_messages_work()
    {
        $request = new WithdrawRequest();

        $invalidData = [
            'user_id' => 'не число',
            'amount' => 0,
        ];

        $validator = $this->app['validator']->make($invalidData, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();

        $this->assertEquals(
            'Идентификатор пользователя должен быть числом.',
            $errors->first('user_id')
        );

        $this->assertEquals(
            'Минимальная сумма списания составляет 0.01.',
            $errors->first('amount')
        );
    }

    /**
     * Проверяет работу кастомных сообщений об ошибках для TransferRequest:
     * Создает невалидные данные и проверяет, что возвращаются правильные кастомные сообщения
     */
    #[Test]
    public function transfer_request_custom_messages_work()
    {
        $request = new TransferRequest();

        $invalidData = [
            'from_user_id' => 'не число',
            'amount' => 0,
        ];

        $validator = $this->app['validator']->make($invalidData, $request->rules(), $request->messages());

        $this->assertFalse($validator->passes());
        $errors = $validator->errors();

        $this->assertEquals(
            'Идентификатор отправителя должен быть числом.',
            $errors->first('from_user_id')
        );

        $this->assertEquals(
            'Идентификатор получателя обязателен.',
            $errors->first('to_user_id')
        );

        $this->assertEquals(
            'Минимальная сумма перевода составляет 0.01.',
            $errors->first('amount')
        );
    }
}
