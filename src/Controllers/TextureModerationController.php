<?php

namespace LittleSkin\TextureModeration\Controllers;

use App\Models\Texture;
use App\Services\Hook;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use LittleSkin\TextureModeration\Models\ModerationRecord;
use LittleSkin\TextureModeration\ReviewState;
use LittleSkin\TextureModeration\RecordSource;

class TextureModerationController extends Controller
{
    public function show(Request $request)
    {
        $states = [
            ReviewState::MANUAL => trans('LittleSkin\TextureModeration::front-end.state.manual'),
            ReviewState::APPROVED => trans('LittleSkin\TextureModeration::front-end.state.approved'),
            ReviewState::REJECTED => trans('LittleSkin\TextureModeration::front-end.state.rejected'),
            ReviewState::USER => trans('LittleSkin\TextureModeration::front-end.state.user'),
            ReviewState::MISS => trans('LittleSkin\TextureModeration::front-end.state.miss'),
        ];

        return view('LittleSkin\TextureModeration::texture-moderation', [
            'states' => $states,
        ]);
    }

    public function manage(Request $request)
    {
        $q = $request->input('q');

        return ModerationRecord::usingSearchString($q)
            ->leftJoin('users as operator', 'operator.uid', '=', 'moderation_records.operator')
            ->leftJoin('textures', 'textures.tid', '=', 'moderation_records.tid')
            ->leftJoin('users', 'users.uid', '=', 'textures.uploader')
            ->select(['textures.uploader', 'users.uid', 'users.nickname', 'moderation_records.*', 'operator.nickname as operator_nickname'])
            ->paginate(9);
    }

    public function review(ModerationRecord $record, Request $request)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject', 'private'])],
        ]);

        $tid = $record->tid;
        $action = $data['action'];

        $record->operator = Auth::user()->uid;

        switch ($action) {
            case 'approve':
                $record->review_state = ReviewState::APPROVED;

                $record->save();

                $texture = Texture::where('tid', $tid)->first();
                $texture->public = true;

                $texture->save();

                $uploader = $texture->owner;
                if ($uploader) {
                    Hook::sendNotification([$uploader], trans('LittleSkin\TextureModeration::skinlib.notification.title'), trans('LittleSkin\TextureModeration::skinlib.notification.approved', [
                        'name' => $texture->name,
                    ]));
                }

                return json(trans('general.op-success'), 0);

                break;
            case 'reject':
                $record->review_state = ReviewState::REJECTED;

                $record->save();
                
                $texture = Texture::where('tid', $tid)->first();
                
                if($record->source === RecordSource::ON_PRIVACY_UPDATED){
                    $texture->public = false;
                    $texture->save();
                    
                    return json(trans('LittleSkin\TextureModeration::manage.message.keep-privacy'), 0);
                }

                $texture->delete();

                $uploader = $texture->owner;
                if ($uploader) {
                    $uploader->score += $texture->size * option('score_per_storage');
                    $uploader->save();

                    Hook::sendNotification([$uploader], trans('LittleSkin\TextureModeration::skinlib.notification.title'), trans('LittleSkin\TextureModeration::skinlib.notification.deleted', [
                        'name' => $texture->name,
                    ]));
                }

                return json(trans('LittleSkin\TextureModeration::manage.message.deleted'), 0);

                break; 
            case 'private':
                $record->review_state = ReviewState::REJECTED;

                $record->save();

                $texture = Texture::where('tid', $tid)->first();

                $uploader = $texture->owner;
                if ($uploader) {
                    $diff = $texture->size * (option('private_score_per_storage') - option('score_per_storage'));

                    if ($uploader->score >= $diff) {
                        $uploader->score -= $diff;
                        $uploader->save();

                        $texture->public = false;
                        $texture->save();

                        Hook::sendNotification([$uploader], trans('LittleSkin\TextureModeration::skinlib.notification.title'), trans('LittleSkin\TextureModeration::skinlib.notification.private', [
                            'name' => $texture->name,
                        ]));

                        return json(trans('LittleSkin\TextureModeration::manage.message.privacy'), 0);
                    } else {
                        $uploader->score += $texture->size * option('score_per_storage');
                        $uploader->save();

                        $texture->delete();

                        Hook::sendNotification([$uploader], trans('LittleSkin\TextureModeration::skinlib.notification.title'), trans('LittleSkin\TextureModeration::skinlib.notification.deleted', [
                            'name' => $texture->name,
                        ]));

                        return json(trans('LittleSkin\TextureModeration::manage.message.privacy-failed'), 1);
                    }
                }

                break;
        }
    }
}
