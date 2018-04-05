<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class holidays extends Model
{
    public static function bulkInsertOrUpdate(array $holidays)
    {
        foreach($holidays as $holiday) {
            static::updateOrCreate(['country' => $holiday['country'], 'name' => $holiday['name'], 'rule' => $holiday['rule']], $holiday);
        }
    }

    protected $fillable = ['country', 'name', 'rule'];

}
