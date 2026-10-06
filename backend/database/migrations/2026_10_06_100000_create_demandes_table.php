<?php

use App\Enums\Statut;
use App\Enums\TypeActe;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('npi', 10);
            $table->enum('type_acte', TypeActe::valeurs());
            $table->unsignedTinyInteger('nombre_copies');
            $table->enum('statut', Statut::valeurs())->default(Statut::DEPOSEE->value);
            $table->text('motif_rejet')->nullable();
            $table->timestamps();

            $table->index(['npi', 'created_at']);
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};
