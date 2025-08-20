<?php

namespace App\Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class CreateProjectsTable
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->string('share_token')->unique();
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
        Capsule::schema()->dropIfExists('projects');
    }
}