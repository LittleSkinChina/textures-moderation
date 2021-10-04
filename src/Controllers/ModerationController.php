<?php

namespace LittleSkin\TextureModeration\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\Texture;
use Illuminate\Support\Facades\DB;
use Image;
use LittleSkin\TextureModeration\ReviewState;

class ModerationController extends Controller
{
  public static function start(Texture $texture)
  {
    $disk = Storage::disk('textures');
    $hash = $texture->hash;
    $file = $disk->get($hash);

    $size = getimagesizefromstring($file);
    if ($size[0] <= 50 || $size[1] <= 50) {
      $texture->state === ReviewState::MISS;
      return $texture->save();
    }
    // @TODO uploader
    if ($texture->state === ReviewState::ACCEPTED) {
      $texture->public = true;
      return $texture->save();
    }
    $record = DB::table('textures')
      ->where('hash', $texture->hash)
      ->first();
    if ($record) {
      $texture->state = $record->state;
      return $texture->save();
    }
  }
}
