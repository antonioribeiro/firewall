<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFirewallTable extends Migration {

	/**
	 * Run the migration.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('firewall', function (Blueprint $table) {
            $table->increments('id');

            $table->string('ip_address', 39)->unique()->index();

            $table->boolean('whitelisted')->default(false); /// default is blacklist

            $table->timestamps();
        });
	}

	/**
	 * Reverse the migration.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::dropIfExists('firewall');
	}

}
