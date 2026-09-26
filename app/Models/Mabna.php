<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mabna extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'gender', 'status'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
