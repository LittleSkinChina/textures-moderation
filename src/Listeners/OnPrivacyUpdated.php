<?php

namespace LittleSkin\TextureModeration\Listeners;

use App\Services\Hook;
use App\Models\Texture;
use LittleSkin\TextureModeration\Controllers\ModerationController;
use LittleSkin\TextureModeration\ReviewState;

class OnPrivacyUpdated
{
  public function handle(Texture $texture)
  {
    if ($texture->public) {
      if ($texture->state === ReviewState::REJECTED) {
        return abort(403, 'rejected');
      } else if (
        $texture->state === ReviewState::MISS
        || $texture->state === ReviewState::MANUAL
        || $texture->state === ReviewState::ACCEPTED
      ) {
        // do nothing
      }else{
        ModerationController::start($texture);
        $texture->public = false;
        $texture->save();
      }
    }
  }
}
