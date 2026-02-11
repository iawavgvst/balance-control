<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для перевода средств
     *
     * @return array<string>
     */
    public function rules(): array
    {
        return [
            'from_user_id' => 'required|integer',
            'to_user_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
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
            'from_user_id.required' => 'Идентификатор отправителя обязателен.',
            'from_user_id.integer' => 'Идентификатор отправителя должен быть числом.',

            'to_user_id.required' => 'Идентификатор получателя обязателен.',
            'to_user_id.integer' => 'Идентификатор получателя должен быть числом.',

            'amount.required' => 'Укажите сумму перевода.',
            'amount.numeric' => 'Сумма перевода должна быть числом.',
            'amount.min' => 'Минимальная сумма перевода составляет 0.01.',
            'amount.max' => 'Сумма перевода не должна превышать 999 999 999 999.99',

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
