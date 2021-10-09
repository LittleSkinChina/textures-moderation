<?php

use App\Models\Texture;
use App\Services\Hook;
use Blessing\Filter;
use Blessing\Rejection;
use LittleSkin\TextureModeration\Models\ModerationRecord;
use LittleSkin\TextureModeration\ReviewState;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use LittleSkin\TextureModeration\Controllers\ModerationController;
use LittleSkin\TextureModeration\Listeners\OnPrivacyUpdated;
use LittleSkin\TextureModeration\Listeners\OnUpload;

return function (Filter $filter, Dispatcher $events) {
    $events->listen('texture.uploaded', OnUpload::class);
    $filter->add('can_update_texture_privacy', function ($init, $texture) {
        // private to public
        if (!$texture->public) {
            $record = DB::table('moderation_records')
                ->where('tid', $texture->tid)
                ->first();
            if (!$record) {
                $result = ModerationController::start($texture);
                return $result ? $result : $texture;
            } else if ($record->review_state === ReviewState::REJECTED) {
                return new Rejection('该材质禁止公开。');
            } else if ($record->review_state === ReviewState::MANUAL) {
                return new Rejection('该材质正在等待管理员审核。');
            }
        }
        return $texture;
    });
    Hook::addScriptFileToPage(plugin_assets('texture-moderation', 'js/texture-moderation.js'), ['admin/texture-moderation']);

    Hook::addRoute(function () {
        Route::namespace('LittleSkin\TextureModeration')
            ->middleware(['web', 'auth', 'role:admin'])
            ->prefix('admin/texture-moderation')
            ->group(function () {
                Route::get('', 'TextureModerationController@show');
                Route::post('review', 'TextureModerationController@review');
                Route::get('list', 'TextureModerationController@manage');
            });

        Route::namespace('LittleSkin\TextureModeration\Controllers')
            ->middleware(['web', 'auth', 'role:admin'])
            ->prefix('admin/moderation-whitelist')
            ->group(function () {
                Route::get('', 'WhitelistController@show');
                Route::post('', 'WhitelistController@add');
                Route::delete('', 'WhitelistController@delete');
            });
    });

    Hook::addMenuItem('admin', 4001, [
        'title' => '材质审核',
        'link' => 'admin/texture-moderation',
        'icon' => 'fa-shield-alt',
    ]);

    Hook::addMenuItem('admin', 4002, [
        'title' => '免审用户',
        'link' => 'admin/moderation-whitelist',
        'icon' => 'fa-shield-alt',
    ]);

    $filter->add('grid:skinlib.show', function ($grid) {
        $texture = request()->route()->parameter('texture');
        $record = ModerationRecord::where('tid', $texture->tid)->first();
        if (optional($record)->review_state === ReviewState::MANUAL) {
            array_unshift($grid['widgets'][0][1], 'LittleSkin\TextureModeration::texture-detail-tip');
        }

        return $grid;
    });
};
