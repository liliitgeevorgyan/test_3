<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClicksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->string('click_id')->unique()->index();
            $table->unsignedBigInteger('offer_id')->index();
            $table->string('source')->index();
            $table->timestamp('timestamp')->index();
            $table->string('signature');
            $table->timestamps();
            
            // Composite indexes for better query performance
            $table->index(['offer_id', 'timestamp']);
            $table->index(['source', 'timestamp']);
            $table->index(['timestamp', 'offer_id', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clicks');
    }
}
