<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('titre', 200);
            $table->string('slug', 220)->unique();
            $table->string('type', 32)->index();
            $table->longText('contenu');
            $table->string('version', 16);
            $table->string('auteur', 120);
            $table->date('date_mise_a_jour')->index();
            $table->timestamps();
        });

        Schema::create('document_piece', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('piece_id')->constrained('pieces')->cascadeOnDelete();
            $table->primary(['document_id', 'piece_id']);
            $table->index('piece_id');
        });

        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16);
            $table->unsignedBigInteger('consultable_id');
            $table->timestampTz('consulte_le')->useCurrent();

            // « Les plus consultés » : regroupement par type et identifiant sur une période.
            $table->index(['type', 'consulte_le', 'consultable_id']);
            // Courbe des 30 derniers jours.
            $table->index('consulte_le');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('document_piece');
        Schema::dropIfExists('documents');
    }
};
