<?php

use App\Models\Flag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        if (!Schema::hasTable('flags')) {
            Schema::create('flags', function (Blueprint $table) {
                $table->id();
                $table->string('capital')->nullable();
                $table->string('code')->nullable();
                $table->string('continent')->nullable();
                $table->string('name')->nullable();
            });


            $url = public_path('country.json');
            $responses = file_get_contents($url);
            $responses = json_decode($responses);

            $values = [];

            foreach ($responses as $response) {

                $data = get_object_vars($response);

                $values[] = [
                    'capital' => $data['capital'] ?? '',
                    'code' => $data['code'],
                    'continent' => $data['continent'] ?? '',
                    'name' => $data['name'],
                ];

            }

            Flag::insert($values);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('flags');
    }

};
