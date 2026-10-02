<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PostgreSQL ne crée pas d'index sur les clés étrangères : chaque colonne
 * *_id reçoit un index explicite. La recherche et les facettes passent par
 * Meilisearch, ces index servent aux jointures, aux suppressions en cascade
 * et à la réindexation par lots.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pieces', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->string('nom', 160);
            $table->text('description');

            $table->foreignId('categorie_id')->index()->constrained('categories')->restrictOnDelete();
            $table->foreignId('echelle_id')->index()->constrained('echelles')->restrictOnDelete();
            $table->foreignId('fabricant_id')->index()->constrained('fabricants')->restrictOnDelete();
            $table->foreignId('materiau_id')->index()->constrained('materiaux')->restrictOnDelete();
            $table->foreignId('periode_id')->index()->constrained('periodes')->restrictOnDelete();
            $table->foreignId('modele_id')->constrained('modeles')->restrictOnDelete();

            $table->decimal('longueur_mm', 8, 2);
            $table->decimal('largeur_mm', 8, 2);
            $table->decimal('hauteur_mm', 8, 2);

            $table->string('chemin_modele_3d')->nullable();
            $table->string('chemin_stl')->nullable();
            $table->string('chemin_miniature')->nullable();

            $table->timestamps();

            // Couvre aussi modele_id seul (colonne de tête). Sert aux pièces du même
            // modèle à la même échelle : compatibilités et seeders.
            $table->index(['modele_id', 'echelle_id']);
        });

        Schema::create('piece_compatibilite', function (Blueprint $table) {
            $table->foreignId('piece_id')->constrained('pieces')->cascadeOnDelete();
            $table->foreignId('compatible_id')->constrained('pieces')->cascadeOnDelete();
            $table->primary(['piece_id', 'compatible_id']);
            $table->index('compatible_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piece_compatibilite');
        Schema::dropIfExists('pieces');
    }
};
