<?php

namespace App\Rules;

use App\Models\Category;
use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Storefront URLs are flat (`/{slug}`), so categories and products share one slug
 * namespace, and slugs must not collide with app routes. Soft-deleted rows count,
 * because their slugs still occupy the unique index.
 */
class UniqueStoreSlug implements ValidationRule
{
    /**
     * Slugs reserved for application routes. Keep in sync with routes/web.php.
     *
     * @var list<string>
     */
    public const RESERVED = [
        'account', 'admin', 'api', 'bag', 'build', 'cart', 'checkout', 'filament',
        'livewire', 'login', 'logout', 'orders', 'password', 'profile', 'quick-view', 'register',
        'search', 'storage', 'up',
    ];

    /**
     * @param  Model|null  $ignore  The record being edited (Category or Product), if any.
     */
    public function __construct(private ?Model $ignore = null) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $slug = strtolower((string) $value);

        if (in_array($slug, self::RESERVED, true)) {
            $fail('এই স্লাগটি সিস্টেমের জন্য সংরক্ষিত, অন্য একটি দিন।');

            return;
        }

        foreach ([Category::class, Product::class] as $modelClass) {
            $query = $modelClass::withTrashed()->where('slug', $slug);

            if ($this->ignore instanceof $modelClass && $this->ignore->exists) {
                $query->whereKeyNot($this->ignore->getKey());
            }

            if ($query->exists()) {
                $fail('এই স্লাগটি আগেই অন্য কোনো ক্যাটাগরি বা পণ্যে ব্যবহার হয়েছে।');

                return;
            }
        }
    }
}
