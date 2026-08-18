<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('key_versions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64);
            $table->jsonb('value');
            $table->timestampTz('recorded_at', 6);
        });

        DB::statement('create index key_versions_as_of_index on key_versions ("key", recorded_at desc, id desc)');

        Schema::create('key_snapshots', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->jsonb('value');
            $table->timestampTz('recorded_at', 6);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('key_snapshots');
        Schema::dropIfExists('key_versions');
    }
};
