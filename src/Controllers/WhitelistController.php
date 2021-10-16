<?php

namespace LittleSkin\TextureModeration\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LittleSkin\TextureModeration\Models\WhitelistItem;

class WhitelistController extends Controller
{
    public function show()
    {
        $items = WhitelistItem::leftJoin('users as targetUser', 'targetUser.uid', '=', 'user_id')
            ->leftJoin('users as operator', 'operator.uid', '=', 'operator')
            ->select(['targetUser.uid', 'targetUser.nickname', 'operator.nickname as operator_nickname', 'created_at'])
            ->get();

        return view('LittleSkin\TextureModeration::whitelist', [
            'items' => $items,
        ]);
    }

    public function add(Request $request)
    {
        $userId = $request->input('userId');

        $user = User::where('uid', $userId)->first();
        if (!$user) {
            abort(403, trans('admin.users.operations.non-existent'));
        }

        $item = WhitelistItem::where('user_id', $userId)->first();
        if ($item) {
            abort(403, '免审用户重复添加');
        } else {
            $item = new WhitelistItem();
            $item->user_id = $userId;
            $item->operator = auth()->id();

            $item->save();

            return redirect('/admin/moderation-whitelist');
        }
    }

    public function delete(Request $request)
    {
        $item = WhitelistItem::where('user_id', $request->input('userId'))->first();
        if ($item) {
            $item->delete();

            return json('删除成功', 0);
        } else {
            return json('删除失败', -1);
        }
    }
}
