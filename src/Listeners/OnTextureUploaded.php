<?php

namespace LittleSkin\TextureModeration\Listeners;

use App\Models\Texture;
use LittleSkin\TextureModeration\Controllers\ModerationController;

class OnTextureUploaded
{
    public function handle(Texture $texture)
    {
        if ($texture->public) {
            $texture->public = false;
            $texture->save();

            return ModerationController::start($texture);
        }
    }
}