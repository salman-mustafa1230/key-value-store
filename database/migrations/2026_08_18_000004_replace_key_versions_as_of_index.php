<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('key_versions', 'key_versions_key_recorded_at_index')) {
            Schema::table('key_versions', function (Blueprint $table) {
                $table->dropIndex('key_versions_key_recorded_at_index');
            });
        }

        if (! Schema::hasIndex('key_versions', 'key_versions_as_of_index')) {
            DB::statement('create index key_versions_as_of_index on key_versions ("key", recorded_at desc, id desc)');
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('key_versions', 'key_versions_as_of_index')) {
            Schema::table('key_versions', function (Blueprint $table) {
                $table->dropIndex('key_versions_as_of_index');
            });
        }

        if (! Schema::hasIndex('key_versions', 'key_versions_key_recorded_at_index')) {
            Schema::table('key_versions', function (Blueprint $table) {
                $table->index(['key', 'recorded_at']);
            });
        }
    }
};
