<?php

namespace App\Http\Requests\Storefront;

use App\Http\Requests\Concerns\NormalizesPhone;
use App\Models\Area;
use App\Rules\BangladeshiPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Checkout form. `area` is an active area id, or "other" with the area typed in `area_other`.
 */
class CheckoutRequest extends FormRequest
{
    use NormalizesPhone;

    public const OTHER_AREA = 'other';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneInput();
        $this->merge(['area' => (string) $this->input('area', '')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $activeAreaIds = Area::query()->where('is_active', true)->pluck('id')->map(fn (int $id): string => (string) $id)->all();

        return [
            'recipient_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', new BangladeshiPhone],
            'area' => ['required', Rule::in([...$activeAreaIds, self::OTHER_AREA])],
            'area_other' => ['nullable', 'required_if:area,'.self::OTHER_AREA, 'string', 'max:100'],
            'address_line' => ['required', 'string', 'max:500'],
            'customer_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipient_name.required' => 'প্রাপকের নাম লিখুন।',
            'phone.required' => 'মোবাইল নম্বর দিন।',
            'area.required' => 'এলাকা বাছাই করুন।',
            'area.in' => 'এলাকা বাছাই করুন।',
            'area_other.required_if' => 'আপনার এলাকার নাম লিখুন।',
            'address_line.required' => 'বাসা/রোড/ল্যান্ডমার্ক লিখুন।',
            'address_line.max' => 'ঠিকানা সর্বোচ্চ ৫০০ অক্ষরের হতে পারে।',
            'customer_note.max' => 'নোট সর্বোচ্চ ৫০০ অক্ষরের হতে পারে।',
        ];
    }

    /**
     * @return array{recipient_name: string, phone: string, area_id: int|null, area_other: string|null, address_line: string}
     */
    public function address(): array
    {
        $isOther = $this->validated('area') === self::OTHER_AREA;

        return [
            'recipient_name' => trim($this->validated('recipient_name')),
            'phone' => $this->validated('phone'),
            'area_id' => $isOther ? null : (int) $this->validated('area'),
            'area_other' => $isOther ? trim((string) $this->validated('area_other')) : null,
            'address_line' => trim($this->validated('address_line')),
        ];
    }
}
