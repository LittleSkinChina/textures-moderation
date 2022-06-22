<?php

use App\Services\Hook;
use Blessing\Filter;
use Blessing\Rejection;
use Illuminate\Contracts\Events\Dispatcher;
use LittleSkin\TextureModeration\Controllers\ModerationController;
use LittleSkin\TextureModeration\Listeners\OnTextureDeleted;
use LittleSkin\TextureModeration\Listeners\OnTextureUploaded;
use LittleSkin\TextureModeration\Models\ModerationRecord;
use LittleSkin\TextureModeration\ReviewState;
use LittleSkin\TextureModeration\RecordSource;

return function (Filter $filter, Dispatcher $events) {
    $events->listen('texture.uploaded', OnTextureUploaded::class);
    $events->listen('texture.deleted', OnTextureDeleted::class);

    $filter->add('can_update_texture_privacy', function ($can, $texture) {
        // private to public
        if (!$texture->public) {
            $record = ModerationRecord::where('tid', $texture->tid)->first();
            if (!$record) {
                $result = ModerationController::start($texture, RecordSource::ON_PRIVACY_UPDATED);

                return $result ? $result : $texture;
            } elseif ($record->review_state === ReviewState::MANUAL) {
                return new Rejection(trans('LittleSkin\TextureModeration::skinlib.manual_tip'));
            } elseif ($record->review_state === ReviewState::REJECTED) {
                return new Rejection(trans('LittleSkin\TextureModeration::skinlib.rejected'));
            }
        }

        return $texture;
    });

    Hook::addScriptFileToPage(plugin_assets('texture-moderation', 'js/texture-moderation.js'), ['admin/texture-moderation']);

    Hook::addRoute(function () {
        Route::namespace('LittleSkin\TextureModeration\Controllers')
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
        'icon' => 'fa-list-alt',
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
