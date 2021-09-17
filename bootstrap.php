<?php

use App\Services\Hook;
use Blessing\Filter;
use LittleSkin\TextureModeration\Models\ModerationRecord;
use LittleSkin\TextureModeration\ReviewState;

return function (Filter $filter) {
    Hook::addScriptFileToPage(plugin_assets('texture-moderation', 'js/texture-moderation.js'), ['admin/texture-moderation']);

    Hook::addRoute(function () {
        Route::namespace('LittleSkin\TextureModeration')
            ->middleware(['web', 'auth', 'role:admin'])
            ->prefix('admin/texture-moderation')
            ->group(function () {
                Route::get('', 'TextureModerationController@show');
                Route::post('review', 'TextureModerationController@review');
            });
    });

    Hook::addMenuItem('admin', 4001, [
        'title' => '材质审核',
        'link' => 'admin/texture-moderation?type=pending',
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
