<?php

namespace MadisonSolutions\DictionaryTest;

use Illuminate\Database\Capsule\Manager as DBCapsule;
use Illuminate\Database\Connection as DBConnection;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    protected function getDummyDatabase(): DBConnection
    {
        $capsule = new DBCapsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $conn = $capsule->getConnection('default');
        $conn->getSchemaBuilder()->create('fruits', function ($table) {
            $table->increments('id');
            $table->string('name', 32);
            $table->string('category', 32);
            $table->integer('sourness');
        });
        $conn->table('fruits')->insert([
            ['id' => 1, 'name' => 'Grape', 'category' => 'Vine', 'sourness' => 3],
            ['id' => 2, 'name' => 'Orange', 'category' => 'Citrus', 'sourness' => 4],
            ['id' => 3, 'name' => 'Banana', 'category' => 'Tropical', 'sourness' => 1],
            ['id' => 4, 'name' => 'Lemon', 'category' => 'Citrus', 'sourness' => 8],
        ]);
        return $conn;
    }
}
