<?php

namespace LittleSkin\TextureModeration\Listeners;

use App\Services\Hook;
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
        ModerationController::start($texture);
      } else if ($record->review_state === ReviewState::REJECTED) {
        return abort(403, 'rejected');
      } else if ($record->review_state === ReviewState::ACCEPTED) {
        return;
      } else if (
        $record->review_state === ReviewState::MISS
        || $record->review_state === ReviewState::MANUAL
        || $record->review_state === ReviewState::ACCEPTED
      ) {
        return;
      }
    }
  }
}
