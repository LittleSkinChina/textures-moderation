<?php

namespace LittleSkin\TextureModeration\Listeners;

use App\Models\Texture;
use LittleSkin\TextureModeration\Controllers\ModerationController;
use LittleSkin\TextureModeration\RecordSource;

class OnTextureUploaded
{
    public function handle(Texture $texture)
    {
        if ($texture->public) {
            return ModerationController::start($texture, RecordSource::ON_PUBLIC_UPLOAD);
        }
    }
}
