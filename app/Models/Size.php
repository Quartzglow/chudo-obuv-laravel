<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Size extends Model
{
    protected $fillable = ['value'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'float',
        ];
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_sizes')->withPivot('quantity')->withTimestamps();
    }

    public function getLabelAttribute(): string
    {
        return self::formatValue($this->value);
    }

    /**
     * Размер в виде «36» или «36,5» (в размерном ряду есть половинные размеры).
     */
    public static function formatValue(float|int|string|null $value): string
    {
        $number = (float) $value;

        return fmod($number, 1.0) === 0.0
            ? (string) (int) $number
            : number_format($number, 1, ',', '');
    }
}
