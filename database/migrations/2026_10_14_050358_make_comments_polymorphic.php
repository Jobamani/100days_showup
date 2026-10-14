<?php

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
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
            $table->renameColumn('post_id', 'commentable_id');
            $table->string('commentable_type')->after('commentable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('commentable_type');
            $table->renameColumn('commentable_id', 'post_id');
            $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete();
        });
    }
};
