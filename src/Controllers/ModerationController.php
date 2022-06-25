<?php

namespace LittleSkin\TextureModeration\Controllers;

use App\Models\Texture;
use Blessing\Rejection;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use LittleSkin\TextureModeration\Models\ModerationRecord;
use LittleSkin\TextureModeration\Models\WhitelistItem;
use LittleSkin\TextureModeration\ReviewState;
use Qcloud\Cos\Client;

require_once dirname(__FILE__).'/../../vendor/cos-sdk-v5-7.phar';

class ModerationController extends Controller
{
    public static function start(Texture $texture, $source, Dispatcher $dispatcher)
    {
        $disk = Storage::disk('textures');
        $hash = $texture->hash;
        $file = $disk->get($hash);

        $dispatcher->dispatch('texture-moderation.starting', [$texture]);

        $record = new ModerationRecord();
        $record->tid = $texture->tid;
        $record->source = $source;
        $size = getimagesizefromstring($file);
        if ($size[0] <= 100 || $size[1] <= 100) {
            $record->review_state = ReviewState::MISS;
            $record->save();

            $dispatcher->dispatch('texture-moderation.finished', [$record]);

            return;
        }

        $whitelist = WhitelistItem::where('user_id', $texture->uploader)->first();
        if ($whitelist) {
            $record->review_state = ReviewState::USER;
            $record->save();

            $dispatcher->dispatch('texture-moderation.finished', [$record]);

            return;
        }

        $textureInDb = Texture::where('hash', $texture->hash)
            ->where('tid', '!=', $texture->tid)
            ->first();
        if ($textureInDb) {
            $itsRecord = ModerationRecord::where('tid', $textureInDb->tid)->first();
            if ($itsRecord) {
                $record = $itsRecord->review_state;
                $record->save();

                $dispatcher->dispatch('texture-moderation.finished', [$record]);

                return;
            }
        }

        $cosClient = new Client([
            'region' => env('COS_REGION'),
            'schema' => 'https',
            'credentials' => [
                'secretId' => env('COS_SECRET_ID'),
                'secretKey' => env('COS_SECRET_KEY'),
            ],
        ]
    );
        $imgUrl = url('/raw/'.$texture->tid.'.png');

        try {
            $result = $cosClient->getObjectSensitiveContentRecognition([
                'Bucket' => env('COS_BUCKET').'-'.env('COS_APP_ID'),
                'Key' => '/',
                'DetectType' => 'porn,politics',
                'DetectUrl' => $imgUrl,
                'ci-process' => 'sensitive-content-recognition',
                'BizType' => env('TEXMOD_BIZTYPE'),
            ]);
        } catch (Exception $e) {
            $record->review_state = ReviewState::MANUAL;
            $record->save();

            return new Rejection(trans('LittleSkin\TextureModeration::skinlib.manual_tip'));
        }

        $record->porn_label = $result['PornInfo'][0]['Label'];
        $record->porn_score = $result['PornInfo'][0]['Score'];

        $record->politics_score = $result['PoliticsInfo'][0]['Score'];
        $record->politics_label = $result['PoliticsInfo'][0]['Label'];

        $threshold = (int) (env('TEXMOD_THRESHOLD'));

        if ($record->porn_score < $threshold && $record->politics_score < $threshold) {
            $texture->public = true;
            $texture->save();

            $record->review_state = ReviewState::APPROVED;
            $record->save();

            $dispatcher->dispatch('texture-moderation.finished', [$record]);

            return;
        }

        $texture->public = false;
        $texture->save();
        
        $record->review_state = ReviewState::MANUAL;
        $record->save();

        $dispatcher->dispatch('texture-moderation.onegai', [$record]);

        return new Rejection(trans('LittleSkin\TextureModeration::skinlib.manual_tip'));
    }
}
