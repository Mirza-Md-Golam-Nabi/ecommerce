<?php

namespace App\Models;

use App\Services\Catalog\CategoryTree;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

/**
 * Category tree node (unlimited depth). See docs/02-database.md.
 *
 * `path` (materialized path like `/1/5/23/`), `depth` and `is_leaf` are
 * maintained automatically by the model events below — never set them by hand.
 * Products may only belong to leaf categories.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string $path
 * @property int $depth
 * @property bool $is_leaf
 * @property bool $is_active
 */
#[Fillable(['parent_id', 'name', 'slug', 'image', 'icon', 'is_active', 'show_on_home', 'home_sort_order'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'path' => '',
        'depth' => 0,
        'is_leaf' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'depth' => 'integer',
            'is_leaf' => 'boolean',
            'is_active' => 'boolean',
            'show_on_home' => 'boolean',
            'home_sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            $category->guardTreePosition();
        });

        static::saved(function (Category $category): void {
            $category->syncTreeAfterSave();
        });

        static::deleted(function (Category $category): void {
            self::refreshLeafFlag($category->parent_id);
            app(CategoryTree::class)->flush();
        });

        static::restored(function (Category $category): void {
            self::refreshLeafFlag($category->parent_id);
            app(CategoryTree::class)->flush();
        });
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * A category can be deleted only when it has no sub-categories and no products.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->children()->exists() && ! $this->products()->exists();
    }

    /**
     * Whether this category may receive sub-categories (it must not hold products).
     */
    public function canHaveChildren(): bool
    {
        return ! $this->products()->exists();
    }

    /**
     * Whether $other is this category or one of its descendants.
     */
    public function containsInSubtree(Category $other): bool
    {
        return $this->path !== '' && str_starts_with($other->path, $this->path);
    }

    private function guardTreePosition(): void
    {
        if ($this->parent_id === null) {
            $this->depth = 0;

            return;
        }

        $parent = self::withTrashed()->findOrFail($this->parent_id);

        if ($this->exists && $this->containsInSubtree($parent)) {
            throw new LogicException('একটি ক্যাটাগরিকে তার নিজের বা নিচের কোনো ক্যাটাগরির ভেতরে রাখা যাবে না।');
        }

        if ($this->isDirty('parent_id') && ! $parent->canHaveChildren()) {
            throw new LogicException('"'.$parent->name.'" ক্যাটাগরিতে পণ্য আছে, তাই এর নিচে সাব-ক্যাটাগরি রাখা যাবে না।');
        }

        $this->depth = $parent->depth + 1;
    }

    private function syncTreeAfterSave(): void
    {
        $original = $this->getOriginal();
        $isInsert = ! array_key_exists($this->getKeyName(), $original);
        $previousParentId = $isInsert ? null : ($original['parent_id'] ?? null);
        $parentMoved = $isInsert || $previousParentId !== $this->parent_id;

        $parentPath = $this->parent_id === null
            ? '/'
            : (string) self::withTrashed()->whereKey($this->parent_id)->value('path');
        $expectedPath = $parentPath.$this->id.'/';

        if ($this->path !== $expectedPath) {
            $oldPath = $this->path;

            $this->path = $expectedPath;
            $this->saveQuietly();

            if ($oldPath !== '') {
                $this->rewriteDescendantPaths($oldPath, $expectedPath);
            }
        }

        if ($parentMoved) {
            self::refreshLeafFlag($previousParentId);
            self::refreshLeafFlag($this->parent_id);
        }

        app(CategoryTree::class)->flush();
    }

    private function rewriteDescendantPaths(string $oldPath, string $newPath): void
    {
        self::withTrashed()
            ->where('path', 'like', $oldPath.'%')
            ->whereKeyNot($this->id)
            ->get()
            ->each(function (Category $descendant) use ($oldPath, $newPath): void {
                $path = $newPath.substr($descendant->path, strlen($oldPath));

                $descendant->forceFill([
                    'path' => $path,
                    'depth' => substr_count($path, '/') - 2,
                ])->saveQuietly();
            });
    }

    private static function refreshLeafFlag(?int $categoryId): void
    {
        if ($categoryId === null) {
            return;
        }

        self::withTrashed()->whereKey($categoryId)->update([
            'is_leaf' => ! self::query()->where('parent_id', $categoryId)->exists(),
        ]);
    }
}
