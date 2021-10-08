<?php

namespace LittleSkin\TextureModeration\Controllers;

use App\Models\User;
use LittleSkin\TextureModeration\Models\WhitelistItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WhitelistController extends Controller
{
    public function show()
    {
        $items = WhitelistItem
            ::select('moderation_whitelist.*')
            ->leftJoin('users as operator', 'operator.uid', '=', 'moderation_whitelist.operator')
            ->leftJoin('users', 'users.uid', '=', 'moderation_whitelist.user_id')
            ->select(['users.uid', 'users.nickname', 'operator.nickname as operator_nickname', 'moderation_whitelist.created_at'])
            ->get();
        return view('LittleSkin\TextureModeration::whitelist', [
            'items' => $items
        ]);
    }

    public function add(Request $request)
    {
        $user = User::where('uid', $request->input('userId'))->first();
        if(!$user){
            return abort(403, '用户不存在');
        }
        $item = new WhitelistItem();
        $item->user_id = $request->input('userId');
        $item->operator = auth()->id();
        $item->save();

        return redirect('/admin/moderation-whitelist');
    }

    public function delete(Request $request)
    {
        WhitelistItem::where('user_id', $request->input('userId'))->delete();

        return json('删除成功', 0);
    }
}
