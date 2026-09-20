<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('t_jamaah_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jamaah_id')->constrained('t_jamaah')->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->boolean('is_checked')->default(false);
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('m_users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['jamaah_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_jamaah_documents');
    }
};