<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'brand',
        'description',
        'composition',
        'price',
        'image',
        'search_text',
    ];

    /**
     * Поисковое поле в нижнем регистре (кириллица в SQLite LIKE чувствительна к регистру).
     */
    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            $product->search_text = Str::lower(trim(implode(' ', array_filter([
                $product->name,
                $product->brand,
                $product->description,
            ]))));
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    /**
     * Подкатегория, к которой относится модель.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Товарные позиции: доступные размеры и количество для заказа.
     *
     * @return BelongsToMany<Size, $this>
     */
    public function sizes(): BelongsToMany
    {
        return $this->belongsToMany(Size::class, 'product_sizes')
            ->withPivot('quantity')
            ->withTimestamps()
            ->orderBy('sizes.value');
    }

    /**
     * Суммарное доступное для заказа количество по всем размерам модели.
     */
    public function getTotalStockAttribute(): int
    {
        return (int) $this->sizes->sum(fn (Size $size) => (int) $size->pivot->quantity);
    }

    /**
     * Суммарный остаток без загрузки связи (один запрос на список).
     */
    public function getAvailableSizesAttribute()
    {
        return $this->sizes->filter(fn (Size $size) => (int) $size->pivot->quantity > 0);
    }

    public function imageUrl(): string
    {
        return asset('images/products/'.$this->image);
    }

    /**
     * Товары, суммарный остаток которых не превышает трёх штук: подсвечиваются цветом #ff8080.
     */
    public function isLowStock(): bool
    {
        return $this->total_stock <= 3;
    }

    /**
     * Фильтрация, сортировка и поиск по каталогу.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $query->when(! empty($filters['q']), function (Builder $query) use ($filters) {
            $term = '%'.Str::lower(trim((string) $filters['q'])).'%';
            $query->where(function (Builder $query) use ($term) {
                $query->where('search_text', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('brand', 'like', $term);
            });
        });

        $query->when(! empty($filters['category_id']), function (Builder $query) use ($filters) {
            $query->where('category_id', (int) $filters['category_id']);
        });

        $query->when(! empty($filters['brand']), function (Builder $query) use ($filters) {
            $query->where('brand', $filters['brand']);
        });

        $query->when(isset($filters['price_min']) && $filters['price_min'] !== null && $filters['price_min'] !== '', function (Builder $query) use ($filters) {
            $query->where('price', '>=', (float) $filters['price_min']);
        });

        $query->when(isset($filters['price_max']) && $filters['price_max'] !== null && $filters['price_max'] !== '', function (Builder $query) use ($filters) {
            $query->where('price', '<=', (float) $filters['price_max']);
        });

        $query->when(! empty($filters['size_id']), function (Builder $query) use ($filters) {
            $query->whereHas('sizes', function (Builder $query) use ($filters) {
                $query->where('sizes.id', (int) $filters['size_id'])
                    ->where('product_sizes.quantity', '>', 0);
            });
        });

        $query->when(! empty($filters['in_stock']), function (Builder $query) {
            $query->whereHas('sizes', fn (Builder $query) => $query->where('product_sizes.quantity', '>', 0));
        });

        return match ($filters['sort'] ?? null) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            default => $query->orderBy('id'),
        };
    }
}
