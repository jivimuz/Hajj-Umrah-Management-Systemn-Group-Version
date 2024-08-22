<?php

use App\Models\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('m_branch', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
        });

        $data = [
            ["name" => "Head Office", "code" => "HEAD"],
        ];
        Branch::insert($data);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_branch');
    }
};
