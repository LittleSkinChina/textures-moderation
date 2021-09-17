<?php

namespace LittleSkin\TextureModeration\Models;

use Illuminate\Database\Eloquent\Model;

class ModerationRecord extends Model
{
    protected $casts = [
        'id' => 'integer',
        'tid' => 'integer',
        'porn_score' => 'integer',
        'politics_score' => 'integer',
        'review_state' => 'integer',
        'operator' => 'integer',
    ];
}
