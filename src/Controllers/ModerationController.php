<?php

namespace LittleSkin\TextureModeration\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\Texture;
use App\Models\User;
use Blessing\Rejection;
use Exception;
use Illuminate\Support\Facades\DB;
use LittleSkin\TextureModeration\Models\ModerationRecord;
use LittleSkin\TextureModeration\Models\WhitelistItem;
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
      $record->review_state = ReviewState::MISS;
      $record->save();
      return $record;
    }
    // @TODO uploader
    $whitelist = WhitelistItem::where('user_id', $texture->uploader)->first();
    if ($whitelist) {
      $record->review_state = ReviewState::USER;
      $record->save();
      return $record;
    }

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
        'region' => env('COS_REGION'),
        'schema' => 'https',
        'credentials' => array(
          'secretId'  => env('COS_SECRET_ID'),
          'secretKey' => env('COS_SECRET_KEY')
        )
      )
    );
    $imgUrl = url('/raw/' . $texture->tid . ".png");
    try {
      $result = $cosClient->getObjectSensitiveContentRecognition(array(
        'Bucket' => env('COS_BUCKET'),
        'Key' => '/',
        'DetectType' => 'porn,politics',
        'DetectUrl' => $imgUrl,
        'ci-process' => 'sensitive-content-recognition',
        'BizType' => env('TEXMOD_BIZTYPE')
      ));
    } catch (Exception $e) {
      $record->review_state = ReviewState::MANUAL;
      $record->save();
      return new Rejection('等待审核');
    }


    $record->porn_label = $result['PornInfo'][0]['Label'];
    $record->porn_score = $result['PornInfo'][0]['Score'];
    $record->politics_score = $result['PoliticsInfo'][0]['Score'];
    $record->politics_label = $result['PoliticsInfo'][0]['Label'];
    $threshold = (int)(env('TEXMOD_THRESHOLD'));
    if ($record->porn_score < $threshold && $record->politics_score < $threshold) {
      $texture->public = true;
      $record->review_state = ReviewState::ACCEPTED;
      $texture->save();
      return;
    }
    $record->review_state = ReviewState::MANUAL;
    $record->save();
    
    return new Rejection('等待审核');
  }
}
