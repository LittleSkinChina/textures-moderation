<?php

namespace LittleSkin\TextureModeration\Models;

use Illuminate\Database\Eloquent\Model;

use Lorisleiva\LaravelSearchString\Concerns\SearchString;


class ModerationRecord extends Model
{
    use SearchString;
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    protected $searchStringColumns = [
        'id', 'tid', 'operator', 'review_state', 'porn_score', 'politics_score', 'porn_label', 'politics_label', 'created_at' => ['date' => true], 'updated_at' => ['date' => true], 
    ];

    protected $casts = [
        'id' => 'integer',
        'tid' => 'integer',
        'porn_score' => 'integer',
        'politics_score' => 'integer',
        'review_state' => 'integer',
        'operator' => 'integer',
    ];

    public function texture(){
        return $this->belongsTo('App\Models\Texture');
    }
}
