
<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::create('incidents', function (Blueprint $table) {
                $table->uuid('id')->primary();

                $table->foreignId('category_id')
                    ->constrained()
                    ->restrictOnDelete();

                $table->geography(
                    'location',
                    subtype: 'point',
                    srid: 4326
                );

                $table->string('status')->default('OPEN');

                $table->timestamps();
            });
        }

        public function down(): void
        {
            Schema::dropIfExists('incidents');
        }
    };
