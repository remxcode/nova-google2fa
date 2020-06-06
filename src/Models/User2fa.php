<?php

namespace Lifeonscreen\Google2fa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\morphTo;

/**
 * Class User2fa
 * @package Lifeonscreen\Google2fa\Models
 */
class User2fa extends Model
{
    /**
     * @var string
     */
    protected $table   = 'google2fa';

    protected $fillable = [
        'google2fa_secret',
        'recovery',
    ];
    /**
     * @return morphTo
     */
    public function google2fable(): morphTo
    {
        return $this->morphTo();
    }
}