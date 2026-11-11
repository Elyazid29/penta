<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_pakets', function (Blueprint $table): void {
            $table->string('sub_kategori', 150)->nullable()->after('kategori');
            $table->string('satuan', 30)->default('paket')->after('harga_paket');
            $table->text('catatan')->nullable()->after('satuan');
        });

        Schema::table('master_paket_items', function (Blueprint $table): void {
            $table->string('nama_item')->nullable()->after('barang_id');
            $table->string('satuan', 30)->default('unit')->after('qty');
            $table->decimal('harga', 15, 2)->default(0)->after('satuan');
            $table->unsignedSmallInteger('urutan')->default(0)->after('harga');
        });

        Schema::table('master_paket_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('barang_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('master_paket_items', function (Blueprint $table): void {
            $table->dropColumn(['nama_item', 'satuan', 'harga', 'urutan']);
        });

        Schema::table('master_pakets', function (Blueprint $table): void {
            $table->dropColumn(['sub_kategori', 'satuan', 'catatan']);
        });
    }
};
