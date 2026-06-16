<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    protected $fillable = [
        'store_id', 'name', 'slug', 'type', 'options', 'unit',
        'is_filterable', 'is_variant', 'is_required', 'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_filterable' => 'boolean',
        'is_variant' => 'boolean',
        'is_required' => 'boolean',
    ];

    public const TYPES = [
        'text' => 'Text',
        'number' => 'Number',
        'select' => 'Select (single)',
        'multiselect' => 'Select (multiple)',
        'boolean' => 'Yes / No',
        'color' => 'Color',
        'date' => 'Date',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_attributes')
            ->withPivot('is_required', 'sort_order')
            ->withTimestamps();
    }

    public function productValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function getOptionsArray(): array
    {
        return $this->options ?? [];
    }
}
