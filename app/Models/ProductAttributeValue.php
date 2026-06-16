<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValue extends Model
{
    protected $fillable = ['product_id', 'attribute_id', 'value', 'value_json'];

    protected $casts = [
        'value_json' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function getDisplayValueAttribute(): string
    {
        if ($this->value_json) {
            return implode(', ', (array) $this->value_json);
        }
        if ($this->attribute->type === 'boolean') {
            return $this->value ? 'Yes' : 'No';
        }
        $unit = $this->attribute->unit ? ' ' . $this->attribute->unit : '';
        return ($this->value ?? '') . $unit;
    }
}
