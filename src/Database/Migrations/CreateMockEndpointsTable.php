<?php

namespace App\Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateMockEndpointsTable
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->create('mock_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('method');
            $table->string('path');
            $table->integer('status_code')->default(200);
            $table->json('headers')->nullable();
            $table->json('response_body')->nullable();
            $table->integer('delay')->default(0);
            $table->boolean('is_dynamic')->default(false);
            $table->json('dynamic_rules')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            // Create an index on method and path for faster lookups
            $table->index(['project_id', 'method', 'path']);
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('mock_endpoints');
    }
}
