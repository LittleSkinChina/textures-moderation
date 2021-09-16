<?php

namespace Asnxthaony\TextureModeration\Controllers;

use Asnxthaony\TextureModeration\Models\WhitelistItem;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WhitelistController extends Controller
{
    public function list(Request $request)
    {
        return WhitelistItem::all();
    }

    public function add(Request $request)
    {
        $item = new WhitelistItem();
        $item->user_id = $request->input('userId');
        $item->operator = auth()->id();
        $item->save();

        return json('添加成功', 0);
    }

    public function delete(Request $request)
    {
        WhitelistItem::where('user_id', $request->input('userId'))->delete();

        return json('删除成功', 0);
    }
}
