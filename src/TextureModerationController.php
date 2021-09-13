<?php

namespace Asnxthaony\TextureModeration;

use App\Models\Texture;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class TextureModerationController extends Controller
{
    public function show(Request $request)
    {
        $type = $request->input('type');

        $records = Texture::orderBy('upload_at', 'desc')
            ->where('public', true)
            ->when($type, function (Builder $query, $type) {
                switch ($type) {
                    case 'pending':
                        return $query->where('state', TextureState::PENDING);
                    case 'accepted':
                        return $query->where('state', TextureState::ACCEPTED);
                    case 'rejected':
                        return $query->where('state', TextureState::REJECTED);
                }
            })
            ->join('users', 'uid', 'uploader')
            ->select(['tid', 'name', 'uploader', 'state', 'upload_at', 'nickname'])
            ->paginate(7)
            ->withQueryString();

        $states = [
            0 => '审核中',
            1 => '审核通过',
            2 => '审核未通过',
        ];

        return view('Asnxthaony\TextureModeration::texture-moderation', ['records' => $records, 'states' => $states]);
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
                $texture = Texture::where('tid', $tid)->first();

                if ($texture) {
                    $texture->state = TextureState::ACCEPTED;

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

                $texture = Texture::where('tid', $tid)->first();

                if ($texture) {
                    $texture->state = TextureState::REJECTED;

                    $texture->save();

                    return json('操作成功', 0);
                } else {
                    return json('材质不存在', 1);
                }

                break;
        }
    }
}
