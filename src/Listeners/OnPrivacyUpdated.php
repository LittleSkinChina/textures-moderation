<?php

namespace LittleSkin\TextureModeration\Listeners;

use App\Models\Texture;
use Illuminate\Support\Facades\DB;
use LittleSkin\TextureModeration\Controllers\ModerationController;
use LittleSkin\TextureModeration\ReviewState;

class OnPrivacyUpdated
{
    public function handle(Texture $texture)
    {
        if ($texture->public) {
            $record = DB::table('moderation_records')
                ->where('tid', $texture->tid)
                ->first();
            if (!$record) {
                $texture->public = false;

                return ModerationController::start($texture);
            } elseif ($record->review_state === ReviewState::REJECTED) {
                return abort(403, 'rejected');
            } elseif ($record->review_state === ReviewState::ACCEPTED) {
                return;
            } elseif (
        $record->review_state === ReviewState::MISS
        || $record->review_state === ReviewState::MANUAL
        || $record->review_state === ReviewState::ACCEPTED
      ) {
                return;
            }
        }
    }
}
