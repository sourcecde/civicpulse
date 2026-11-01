
<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::table('reports', function (Blueprint $table) {
                $table->foreignUuid('incident_id')
                    ->nullable()
                    ->constrained('incidents')
                    ->nullOnDelete();
            });
        }

        public function down(): void
        {
            Schema::table('reports', function (Blueprint $table) {
                $table->dropConstrainedForeignId('incident_id');
            });
        }
    };
