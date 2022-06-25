<?php

use App\Models\Scope;

return [
    App\Events\PluginWasEnabled::class => function () {
        if (!Schema::hasTable('moderation_whitelist')) {
            Schema::create('moderation_whitelist', function ($table) {
                $table->increments('id');
                $table->integer('user_id')->unique();
                $table->integer('operator');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('moderation_records')) {
            Schema::create('moderation_records', function ($table) {
                $table->increments('id');
                $table->integer('tid')->unique();
                $table->integer('porn_score')->default(-1);
                $table->string('porn_label')->default('');
                $table->integer('politics_score')->default(-1);
                $table->string('politics_label')->default('');
                $table->integer('review_state');
                $table->integer('source');
                $table->integer('operator')->nullable();
                $table->timestamps();
            });
        }

        if(!Scope::where('name', 'TextureModeration.Read')->exists()) {
            Scope::create([
                'name' => 'TextureModeration.Read',
                'description' => 'Ability to read texture moderation records'
            ]);
        }

        if(!Scope::where('name', 'TextureModeration.ReadWrite')->exists()) {
            Scope::create([
                'name' => 'TextureModeration.ReadWrite',
                'description' => 'Ability to read and write texture moderation records'
            ]);
        }
    },
];
