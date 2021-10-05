<?php

namespace LittleSkin\TextureModeration;

use App\Models\Texture;
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
        $type = $request->input('type');

        $records = DB::table('moderation_records', 'mr')
            ->where('review_state', $type)
            ->leftJoin('textures', 'textures.tid', '=', 'mr.tid')
            ->leftJoin('users', 'users.uid', '=', 'textures.uploader')
            ->select(['mr.tid', 'textures.uploader', 'users.uid', 'users.nickname', 'mr.operator', 'mr.updated_at', 'mr.review_state'])
            ->paginate(7);

        $states = [
            ReviewState::PENDING => '正在处理',
            ReviewState::ACCEPTED => '审核通过',
            ReviewState::REJECTED => '审核拒绝',
            ReviewState::USER => '用户免审',
            ReviewState::MISS => '无需审核'
        ];
        return view('LittleSkin\TextureModeration::texture-moderation', ['records' => $records, 'states' => $states]);
    }

    public function review(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'action' => ['required', Rule::in(['accept', 'reject'])],
        ]);

        $tid = $data['id'];
        $action = $data['action'];

        switch ($action) {
            case 'accept':
                $texture = ModerationRecord::where('tid', $tid)->first();

                if ($texture) {
                    $texture->operator = Auth::user()->uid;
                    $texture->review_state = ReviewState::ACCEPTED;

                    $texture->save();

                    return json('操作成功', 0);
                } else {
                    return json('材质不存在', 1);
                }

                break;
            case 'reject':
                $reason = $request->input('reason');

                if (empty($reason)) {
                    return json('理由不能为空', 1);
                }

                $texture = ModerationRecord::where('tid', $tid)->first();

                if ($texture) {
                    $texture->operator = Auth::user()->uid;
                    $texture->review_state = ReviewState::REJECTED;

                    $texture->save();

                    return json('操作成功', 0);
                } else {
                    return json('材质不存在', 1);
                }

                break;
        }
    }
}
