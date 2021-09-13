<?php

use Illuminate\Support\Facades\Schema;

return [
    App\Events\PluginWasEnabled::class => function () {
        if (!Schema::hasColumn('textures', 'state')) {
            Schema::table('textures', function ($table) {
                $table->tinyInteger('state')->after('likes')->default(0);
            });
        }
    },
];
