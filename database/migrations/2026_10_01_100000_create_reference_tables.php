<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 80)->unique();
            $table->string('slug', 80)->unique();
            // Clé de la forme procédurale utilisée par la visionneuse 3D.
            $table->string('forme', 40);
            $table->timestamps();
        });

        Schema::create('echelles', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 16)->unique();
            $table->string('slug', 16)->unique();
            // Dénominateur de l'échelle : 18 pour 1/18. Sert au tri.
            $table->unsignedSmallInteger('rapport')->unique();
            $table->timestamps();
        });

        Schema::create('fabricants', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 120)->unique();
            $table->string('slug', 120)->unique();
            $table->string('pays', 60)->nullable();
            $table->timestamps();
        });

        Schema::create('materiaux', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 80)->unique();
            $table->string('slug', 80)->unique();
            $table->timestamps();
        });

        Schema::create('periodes', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 60)->unique();
            $table->string('slug', 60)->unique();
            $table->unsignedSmallInteger('annee_debut')->nullable();
            $table->unsignedSmallInteger('annee_fin')->nullable();
            $table->timestamps();
        });

        Schema::create('modeles', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 120)->unique();
            $table->string('slug', 120)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modeles');
        Schema::dropIfExists('periodes');
        Schema::dropIfExists('materiaux');
        Schema::dropIfExists('fabricants');
        Schema::dropIfExists('echelles');
        Schema::dropIfExists('categories');
    }
};
