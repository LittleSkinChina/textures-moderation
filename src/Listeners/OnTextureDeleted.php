<?php

namespace LittleSkin\TextureModeration\Listeners;

use App\Models\Texture;
use LittleSkin\TextureModeration\Models\ModerationRecord;
use LittleSkin\TextureModeration\ReviewState;

class OnTextureDeleted
{
    public function handle(Texture $texture)
    {
        // 通过审核记录来判断
        $record = ModerationRecord::where('tid', $texture->tid)->first();

        if ($record && ($record->review_state === ReviewState::REJECTED || $record->review_state === ReviewState::MANUAL) && !$texture->public) {
            $uploader = $texture->owner;
            if ($uploader) {
                // 退回的是 private 的积分, 扣除多余的部分
                $diff = $texture->size * (option('private_score_per_storage') - option('score_per_storage'));

                $uploader->score -= $diff;
                $uploader->save();
            }
        }

        if ($record && $record->review_state === ReviewState::MANUAL) {
            $record->delete();
        }
    }
}
