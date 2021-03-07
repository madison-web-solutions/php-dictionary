<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use MadisonSolutions\Dictionary\DictionaryProvider;
use MadisonSolutions\Dictionary\SearchableDictionary;
use MadisonSolutions\Dictionary\SimpleDatabaseDictionary;
use MadisonSolutions\Dictionary\SimpleStaticDictionary;
use MadisonSolutions\Dictionary\Dictionary;
use PHPUnit\Framework\TestCase;

class DictionaryProviderTest extends TestCase
{
    public function testDictionaryProvider()
    {
        $capsule = new Capsule();
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

        $provider = new DictionaryProvider();
        $counter = 0;
        $provider->registerGetter(function (string $name) use (&$counter, $conn) {
            switch ($name) {
                case 'veg':
                    $counter++;
                    return new SimpleStaticDictionary([
                        'cabbage' => [
                            'label' => 'Cabbage',
                            'variants' => ['red', 'white'],
                        ],
                        'potato' => [
                            'label' => 'Potato',
                            'plural' => 'Potatoes',
                        ],
                    ]);
                case 'fruit':
                    $counter++;
                    return new SimpleDatabaseDictionary(function () use ($conn) {
                        return $conn->table('fruits')
                            ->select(['id', 'name as label', 'category']);
                    }, []);
            }
            return null;
        });

        $this->assertInstanceOf(Dictionary::class, $provider->getDictionary('veg'));
        $this->assertEquals(1, $counter);
        $this->assertEquals('Cabbage', $provider->getDictionary('veg')->label('cabbage'));
        $this->assertEquals(1, $counter);

        $this->assertInstanceOf(Dictionary::class, $provider->getDictionary('fruit'));
        $this->assertEquals(2, $counter);
        $this->assertEquals('Grape', $provider->getDictionary('fruit')->label(1));
        $this->assertEquals(2, $counter);

        $this->assertInstanceOf(SearchableDictionary::class, $provider->getSearchableDictionary('fruit'));
        $this->assertEquals(2, $counter);

        $this->assertNull($provider->getDictionary('teapot'));
        $this->assertNull($provider->getSearchableDictionary('veg'));
    }
}
