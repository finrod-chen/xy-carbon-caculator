<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 稽核鏈的鏈尾狀態（WP0.3 修正）。
 *
 * 原本每筆寫入都用 orderByDesc('id') 讀上一筆 hash，兩個請求同時寫入會分叉。
 * 改成單列狀態表，寫入時在交易內鎖住這一列；並把既有紀錄的尾端接上。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_chain', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('last_id')->nullable();
            $table->string('last_hash', 64)->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        $tail = DB::table('audit_logs')->orderByDesc('id')->first();

        DB::table('audit_chain')->insert([
            'id' => 1,
            'last_id' => $tail->id ?? null,
            'last_hash' => $tail->hash ?? null,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_chain');
    }
};
