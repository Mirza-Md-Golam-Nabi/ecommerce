<?php

namespace App\Http\Requests\Storefront;

use App\Http\Requests\Concerns\NormalizesPhone;
use App\Rules\BangladeshiPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    use NormalizesPhone;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneInput();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', new BangladeshiPhone, 'unique:customers,phone'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'আপনার নাম লিখুন।',
            'name.max' => 'নাম সর্বোচ্চ ১০০ অক্ষরের হতে পারে।',
            'phone.required' => 'মোবাইল নম্বর দিন।',
            'phone.unique' => 'এই নম্বর দিয়ে আগেই অ্যাকাউন্ট খোলা হয়েছে। লগইন করুন।',
            'password.required' => 'পাসওয়ার্ড দিন।',
            'password.min' => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।',
            'password.confirmed' => 'দুই পাসওয়ার্ড মিলছে না।',
        ];
    }
}
