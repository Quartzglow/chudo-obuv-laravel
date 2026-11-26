<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Товарная позиция: сочетание модели и размера.
 */
class ProductSize extends Model
{
    protected $table = 'product_sizes';

    protected $fillable = ['product_id', 'size_id', 'quantity'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Size, $this>
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
