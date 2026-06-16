<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = ['division_id', 'name', 'bn_name'];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function thanas(): HasMany
    {
        return $this->hasMany(Thana::class);
    }
}
