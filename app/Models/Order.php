<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_search',
        'ordered_at',
        'total',
        'stock_applied',
    ];

    /**
     * Поисковое поле ФИО в нижнем регистре (для поиска по кириллице в SQLite).
     */
    protected static function booted(): void
    {
        static::saving(function (Order $order) {
            $order->customer_search = Str::lower(trim((string) $order->customer_name));
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ordered_at' => 'date',
            'total' => 'decimal:2',
            'stock_applied' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Дата заказа в формате дд.мм.гггг.
     */
    public function getOrderedAtLabelAttribute(): string
    {
        return $this->ordered_at?->format('d.m.Y') ?? '';
    }

    /**
     * Количество пар обуви в заказе.
     */
    public function getPairsCountAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }
}
