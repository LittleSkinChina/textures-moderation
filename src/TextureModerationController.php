<?php

namespace LittleSkin\TextureModeration;

use App\Models\Texture;
use App\Models\User;
use App\Services\Hook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use LittleSkin\TextureModeration\Models\ModerationRecord;

class TextureModerationController extends Controller
{
    public function show(Request $request)
    {
        $states = [
            ReviewState::ACCEPTED => trans('LittleSkin\TextureModeration::front-end.state.accepted'),
            ReviewState::REJECTED => trans('LittleSkin\TextureModeration::front-end.state.rejected'),
            ReviewState::USER => trans('LittleSkin\TextureModeration::front-end.state.user'),
            ReviewState::MISS => trans('LittleSkin\TextureModeration::front-end.state.miss'),
            ReviewState::MANUAL => trans('LittleSkin\TextureModeration::front-end.state.manual'),
        ];
        return view('LittleSkin\TextureModeration::texture-moderation', [
            'states' => $states
        ]);
    }

    public function manage(Request $request)
    {
        $q = $request->input('q');

        return ModerationRecord
            ::select('moderation_records.*')
            ->usingSearchString($q)
            ->leftJoin('users as operator', 'operator.uid', '=', 'moderation_records.operator')
            ->leftJoin('textures', 'textures.tid', '=', 'moderation_records.tid')
            ->leftJoin('users', 'users.uid', '=', 'textures.uploader')
            ->select(['textures.uploader', 'users.uid', 'users.nickname', 'moderation_records.*', 'operator.nickname as operator_nickname'])
            ->paginate(9);
    }

    public function review(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'action' => ['required', Rule::in(['accept', 'reject', 'private'])],
        ]);

        $tid = $data['id'];
        $action = $data['action'];

        switch ($action) {
            case 'accept':
                $record = ModerationRecord::where('tid', $tid)->first();

                if ($record) {
                    $record->operator = Auth::user()->uid;
                    $record->review_state = ReviewState::ACCEPTED;

                    $record->save();

                    $texture = Texture::where('tid', $tid)->first();
                    $texture->public = true;
                    $texture->save();
                    $user = User::where('uid', $texture->uploader)->first();

                    Hook::sendNotification([$user], trans('LittleSkin\TextureModeration::skinlib.notification.title'), trans('LittleSkin\TextureModeration::skinlib.notification.accepted', [
                        'name' => $texture->name
                    ]));

                    return json(trans('general.op-success'), 0);
                } else {
                    return json(trans('LittleSkin\TextureModeration::manage.message.texture-not-exist'), 1);
                }

                break;
            case 'reject':
                $record = ModerationRecord::where('tid', $tid)->first();

                if ($record) {
                    $record->operator = Auth::user()->uid;
                    $record->review_state = ReviewState::REJECTED;

                    $record->save();
                    $texture = Texture::where('tid', $tid)->first();
                    $texture->delete();

                    $user = User::where('uid', $texture->uploader)->first();
                    $size = $texture->size;
                    $user->score += $size * option('score_per_storage');
                    $user->save();

                    Hook::sendNotification([$user], trans('LittleSkin\TextureModeration::skinlib.notification.title'), trans('LittleSkin\TextureModeration::skinlib.notification.deleted', [
                        'name' => $texture->name
                    ]));

                    return json(trans('LittleSkin\TextureModeration::manage.message.deleted'), 0);
                } else {
                    return json(trans('LittleSkin\TextureModeration::manage.message.texture-not-exist'), 1);
                }

                break;
            case 'private':
                $record = ModerationRecord::where('tid', $tid)->first();

                if ($record) {
                    $record->operator = Auth::user()->uid;
                    $record->review_state = ReviewState::REJECTED;

                    $record->save();

                    $texture = Texture::where('tid', $tid)->first();
                    $user = User::where('uid', $texture->uploader)->first();
                    $size = $texture->size;

                    $diff = $size * (option('private_score_per_storage') - option('score_per_storage'));
                    if ($user->score >= $diff) {
                        $user->score -= $diff;
                        $user->save();
                        $texture->public = false;
                        $texture->save();
                        Hook::sendNotification([$user], trans('LittleSkin\TextureModeration::skinlib.notification.title'), trans('LittleSkin\TextureModeration::skinlib.notification.private', [
                            'name' => $texture->name
                        ]));
                        return json(trans('LittleSkin\TextureModeration::manage.message.privacy'), 0);
                    } else {
                        $user->score += $size * option('score_per_storage');
                        $user->save();
                        $texture->delete();

                        Hook::sendNotification([$user], trans('LittleSkin\TextureModeration::skinlib.notification.title'), trans('LittleSkin\TextureModeration::skinlib.notification.deleted', [
                            'name' => $texture->name
                        ]));

                        return json(trans('LittleSkin\TextureModeration::manage.message.privacy-failed'), 0);
                    }
                } else {
                    return json(trans('LittleSkin\TextureModeration::manage.message.texture-not-exist'), 1);
                }
                break;
        }
    }
}
