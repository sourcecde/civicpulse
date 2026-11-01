
<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Support\Facades\DB;

    return new class extends Migration
    {
        public function up(): void
        {
            DB::statement(
                'CREATE INDEX incidents_location_gist_idx
             ON incidents USING GIST (location)'
            );
        }

        public function down(): void
        {
            DB::statement(
                'DROP INDEX IF EXISTS incidents_location_gist_idx'
            );
        }
    };
