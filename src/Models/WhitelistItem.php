<?php

use Illuminate\Database\Eloquent\Model;

class WhitelistItem extends Model
{
    protected $table = 'moderation_whitelist';

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'operator' => 'integer',
    ];
}
