<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DepositRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для начисления средств
     *
     * @return array<string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer',
            'amount' =>  'required|numeric|min:0.01|max:999999999999.99',
            'comment' => 'nullable|string|max:255',
        ];
    }

    /**
     * Кастомные сообщения
     *
     * @return array<string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Идентификатор пользователя обязателен.',
            'user_id.integer' => 'Идентификатор пользователя должен быть числом.',

            'amount.required' => 'Укажите сумму пополнения.',
            'amount.numeric' => 'Сумма пополнения должна быть числом.',
            'amount.min' => 'Минимальная сумма пополнения составляет 0.01.',
            'amount.max' => 'Сумма пополнения не должна превышать 999 999 999 999.99',

            'comment.string' => 'Комментарий должен быть строкой.',
            'comment.max' => 'Комментарий не может быть длиннее 255 символов.',
        ];
    }

    /**
     * Обработка ошибки (ошибок) валидации
     *
     * @param  Validator  $validator
     * @return void
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Ошибка валидации данных.',
                'error' => $validator->errors()
            ], 422)
        );
    }
}
