<?php

namespace LittleSkin\TextureModeration\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\Texture;
use Illuminate\Support\Facades\DB;
use LittleSkin\TextureModeration\Models\ModerationRecord;
use LittleSkin\TextureModeration\ReviewState;
use Qcloud\Cos\Client;

require_once(dirname(__FILE__) . '/../../vendor/cos-sdk-v5-7.phar');

class ModerationController extends Controller
{
  public static function start(Texture $texture)
  {
    $disk = Storage::disk('textures');
    $hash = $texture->hash;
    $file = $disk->get($hash);

    $record = new ModerationRecord;
    $record->tid = $texture->tid;
    $size = getimagesizefromstring($file);
    if ($size[0] <= 50 || $size[1] <= 50) {
      $record->review_state === ReviewState::MISS;
      $record->save();
      return $record;
    }
    // @TODO uploader

    $textureInDb = DB::table('textures')
      ->where('hash', $texture->hash)
      ->where('tid', '!=', $texture->tid)
      ->first();
    if ($textureInDb) {
      $itsRecord = DB::table('moderation_records')
        ->where('tid', $textureInDb->tid);
      $record = $itsRecord->review_state;
      return $record;
    }

    $cosClient = new Client(
      array(
        'region' => env('TEXMOD_REGION'),
        'schema' => 'https',
        'credentials' => array(
          'secretId'  => env('TEXMOD_SECRETID'),
          'secretKey' => env('TEXMOD_SECRETKEY')
        )
      )
    );
    $imgUrl = url('/raw/' . $texture->tid . ".png");
    $result = $cosClient->getObjectSensitiveContentRecognition(array(
      'Bucket' => env('TEXMOD_BUCKET'),
      'Key' => '/',
      'DetectType' => 'porn,politics',
      'DetectUrl' => $imgUrl,
      'ci-process' => 'sensitive-content-recognition',
      'BizType' => env('TEXMOD_BIZTYPE')
    ));


    $record->porn_label = $result['PornInfo'][0]['Label'];
    $record->porn_score = $result['PornInfo'][0]['Score'];
    $record->politics_score = $result['PoliticsInfo'][0]['Score'];
    $record->politics_label = $result['PoliticsInfo'][0]['Label'];
    $threshold = (int)(env('TEXMOD_THRESHOLD'));
    if ($record->porn_score < $threshold && $record->politics_score < $threshold) {
      $texture->public = true;
      $record->review_state = ReviewState::ACCEPTED;
      return $texture->save();
    }
    $record->review_state = ReviewState::MANUAL;
    $record->save();
  }
}
