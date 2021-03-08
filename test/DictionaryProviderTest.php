<?php

namespace MadisonSolutions\DictionaryTest;

use MadisonSolutions\Dictionary\DictionaryProvider;
use MadisonSolutions\Dictionary\SearchableDictionary;
use MadisonSolutions\Dictionary\SimpleDatabaseDictionary;
use MadisonSolutions\Dictionary\SimpleStaticDictionary;
use MadisonSolutions\Dictionary\Dictionary;

class DictionaryProviderTest extends BaseTest
{
    public function testDictionaryProvider()
    {
        $conn = $this->getDummyDatabase();
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
