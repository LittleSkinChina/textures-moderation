<?php
namespace LittleSkin\TextureModeration\Listeners;

use App\Models\Texture;
use App\Services\Hook;
use LittleSkin\TextureModeration\Controllers\ModerationController;
use LittleSkin\TextureModeration\ReviewState;

class OnUpload
{
  public function handle(Texture $texture){
    $texture->state === ReviewState::PENDING;
    $texture->public = false;
    $texture->save();
    ModerationController::start($texture);
  }
}