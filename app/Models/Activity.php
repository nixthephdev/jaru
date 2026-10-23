<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['what'];

    public static function log(string $what): void
    {
        static::create(['what' => $what]);
        static::where('id', '<=', static::max('id') - 200)->delete();
    }
}
