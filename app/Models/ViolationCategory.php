<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViolationCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'points'];

    protected function casts(): array
    {
        return ['points' => 'integer'];
    }

    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }
}
