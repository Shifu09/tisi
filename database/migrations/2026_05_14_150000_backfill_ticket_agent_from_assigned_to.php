<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ticket_agent')) {
            return;
        }

        DB::table('tickets')
            ->whereNotNull('assigned_to')
            ->orderBy('id')
            ->chunkById(100, function ($tickets): void {
                foreach ($tickets as $ticket) {
                    $exists = DB::table('ticket_agent')
                        ->where('ticket_id', $ticket->id)
                        ->where('user_id', $ticket->assigned_to)
                        ->exists();

                    if (! $exists) {
                        DB::table('ticket_agent')->insert([
                            'ticket_id' => $ticket->id,
                            'user_id' => $ticket->assigned_to,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: no borramos filas del pivot por reversión
    }
};
