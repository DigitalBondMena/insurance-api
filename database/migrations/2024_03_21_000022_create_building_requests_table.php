<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBuildingRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('building_requests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('category_id')->nullable();
            $table->bigInteger('building_insurance_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('building_insurance_number')->nullable();
            $table->string('admin_building_insurance_number')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('gender')->nullable();
            $table->bigInteger('building_type_id')->nullable();
            $table->string('building_type')->nullable();
            $table->bigInteger('building_country_id')->nullable();
            $table->string('building_country')->nullable();
            $table->string('building_city')->nullable();
            $table->decimal('building_price', 10, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->string('duration')->nullable();
            $table->date('end_date')->nullable();
            $table->string('active_status')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('building_requests');
    }
} 