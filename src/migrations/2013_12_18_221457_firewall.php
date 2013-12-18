<?php

use Illuminate\Database\Migrations\Migration;

class Firewall extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('firewall', function($table)
        {
            $table->increments('id');

            $table->text('ip_address')->unique()->index();

            $table->boolean('whitelisted')->default(false); /// default is blacklist
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('firewall');
    }

}