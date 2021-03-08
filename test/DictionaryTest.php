<?php

namespace MadisonSolutions\DictionaryTest;

use Illuminate\Database\Query\Builder as QueryBuilder;
use MadisonSolutions\Dictionary\DatabaseDictionary;
use MadisonSolutions\Dictionary\DictionarySearchResult;
use MadisonSolutions\Dictionary\DictionaryValue;
use MadisonSolutions\Dictionary\SimpleDatabaseDictionary;
use MadisonSolutions\Dictionary\SimpleStaticDictionary;
use MadisonSolutions\Dictionary\Dictionary;
use MadisonSolutions\Dictionary\StaticDictionary;

class DictionaryTest extends BaseTest
{
    protected function assertValueInDictionary($key, string $label, array $meta, Dictionary $dict)
    {
        $this->assertTrue($dict->has($key));
        $this->assertEquals($label, $dict->label($key));
        foreach ($meta as $meta_key => $meta_value) {
            $this->assertEquals($meta_value, $dict->meta($key, $meta_key));
        }
        $value = $dict->get($key);
        $this->assertInstanceOf(DictionaryValue::class, $value);
        $this->assertDictionaryValue($key, $label, $meta, $value);
    }

    protected function assertDictionaryValue($key, string $label, array $meta, DictionaryValue $value)
    {
        $this->assertEquals($key, $value->key);
        $this->assertEquals($label, $value->label);
        $this->assertEquals($meta, $value->meta);
        foreach ($meta as $meta_key => $meta_value) {
            $this->assertEquals($meta_value, $value->$meta_key);
        }
    }

    protected function assertDictionary(array $expected_values, $dict)
    {
        $this->assertInstanceOf(Dictionary::class, $dict);
        foreach ($expected_values as [$key, $label, $meta]) {
            $this->assertValueInDictionary($key, $label, $meta, $dict);
        }
        $all_values = $dict->all();
        $this->assertCount(count($expected_values), $all_values);
        foreach ($expected_values as $i => [$key, $label, $meta]) {
            $this->assertDictionaryValue($key, $label, $meta, $all_values[$i]);
        }
        $expected_keys = array_map(function ($item) {
            return $item[0];
        }, $expected_values);
        $this->assertEquals($expected_keys, $dict->allKeys());
    }

    public function testStaticDictionary()
    {
        $statuses = new class extends StaticDictionary {
            protected function defns(): array
            {
                return [
                    'ok' => [
                        'label' => 'OK',
                        'code' => 200,
                    ],
                    'not_found' => [
                        'label' => 'Page Not Found',
                        'code' => 404,
                    ]
                ];
            }
        };

        $this->assertDictionary([
            ['ok', 'OK', ['code' => 200]],
            ['not_found', 'Page Not Found', ['code' => 404]],
        ], $statuses);

        $this->assertNull($statuses->meta('ok', 'foo'));

        $bad_keys = ['teapot', '', null, -1];
        foreach ($bad_keys as $bad_key) {
            $this->assertNull($statuses->get($bad_key));
            $this->assertFalse($statuses->has($bad_key));
            $this->assertNull($statuses->label($bad_key));
            $this->assertNull($statuses->meta($bad_key, 'code'));
        }
    }

    public function testSimpleStaticDictionary()
    {
        $fruits = SimpleStaticDictionary::fromKeys(['apple', 'banana', 'pear']);
        $this->assertInstanceOf(SimpleStaticDictionary::class, $fruits);
        $this->assertDictionary([
            ['apple', 'Apple', []],
            ['banana', 'Banana', []],
            ['pear', 'Pear', []],
        ], $fruits);

        $cheeses = SimpleStaticDictionary::fromKeysAndLabels([
            'edam' => 'Dutch Edam',
            'moz' => 'Mozzarella',
        ]);
        $this->assertInstanceOf(SimpleStaticDictionary::class, $cheeses);
        $this->assertDictionary([
            ['edam', 'Dutch Edam', []],
            ['moz', 'Mozzarella', []],
        ], $cheeses);

        $veg = new SimpleStaticDictionary([
            'cabbage' => [
                'label' => 'Cabbage',
                'variants' => ['red', 'white'],
            ],
            'potato' => [
                'label' => 'Potato',
                'plural' => 'Potatoes',
            ],
        ]);
        $this->assertInstanceOf(SimpleStaticDictionary::class, $veg);
        $this->assertDictionary([
            ['cabbage', 'Cabbage', ['variants' => ['red', 'white']]],
            ['potato', 'Potato', ['plural' => 'Potatoes']],
        ], $veg);
    }

    public function testEmptyStaticDictionary()
    {
        $dict = new class extends StaticDictionary {
            protected function defns(): array
            {
                return [];
            }
        };

        $this->assertDictionary([], $dict);
    }

    public function testDictionaryValueSerialization()
    {
        $apple = new DictionaryValue('apple', 'Apple', ['sweet' => true]);
        $this->assertEquals([
            'key' => 'apple',
            'label' => 'Apple',
            'meta' => ['sweet' => true],
        ], $apple->toArray());
        $json = json_encode($apple);
        $this->assertEquals([
            'key' => 'apple',
            'label' => 'Apple',
            'meta' => ['sweet' => true],
        ], json_decode($json, true));
    }

    public function testDatabaseDictionary()
    {
        $dict = new class extends DatabaseDictionary {
            public $conn;
            protected function getBaseQuery(): QueryBuilder
            {
                return $this->conn->table('fruits')
                    ->select(['id', 'name as label', 'category']);
            }
        };
        $dict->conn = $this->getDummyDatabase();

        $this->assertDictionary([
            [1, 'Grape', ['category' => 'Vine']],
            [2, 'Orange', ['category' => 'Citrus']],
            [3, 'Banana', ['category' => 'Tropical']],
            [4, 'Lemon', ['category' => 'Citrus']],
        ], $dict);

        $this->assertNull($dict->meta(1, 'foo'));

        $bad_keys = ['teapot', '', null, -1];
        foreach ($bad_keys as $bad_key) {
            $this->assertNull($dict->get($bad_key));
            $this->assertFalse($dict->has($bad_key));
            $this->assertNull($dict->label($bad_key));
            $this->assertNull($dict->meta($bad_key, 'category'));
        }
    }

    public function testSimpleDatabaseDictionary()
    {
        $conn = $this->getDummyDatabase();

        $dict1 = new SimpleDatabaseDictionary(function () use ($conn) {
            return $conn->table('fruits')
                ->select(['id', 'name', 'category']);
        }, [
            'label' => 'name',
        ]);
        $this->assertDictionary([
            [1, 'Grape', ['category' => 'Vine']],
            [2, 'Orange', ['category' => 'Citrus']],
            [3, 'Banana', ['category' => 'Tropical']],
            [4, 'Lemon', ['category' => 'Citrus']],
        ], $dict1);

        $bad_keys = ['teapot', '', null, -1];
        foreach ($bad_keys as $bad_key) {
            $this->assertNull($dict1->get($bad_key));
            $this->assertFalse($dict1->has($bad_key));
            $this->assertNull($dict1->label($bad_key));
            $this->assertNull($dict1->meta($bad_key, 'category'));
        }

        $dict2 = new SimpleDatabaseDictionary(function () use ($conn) {
            return $conn->table('fruits')
                ->where('category', '=', 'Citrus')
                ->select(['id', 'name as label', 'sourness']);
        }, []);
        $this->assertDictionary([
            [2, 'Orange', ['sourness' => 4]],
            [4, 'Lemon', ['sourness' => 8]],
        ], $dict2);

        $dict2 = new SimpleDatabaseDictionary(function () use ($conn) {
            return $conn->table('fruits')
                ->where('category', '=', 'Citrus')
                ->select(['id', 'name']);
        }, [
            'label' => function ($row) {
                return "{$row->id}: {$row->name}";
            }
        ]);
        $this->assertDictionary([
            [2, '2: Orange', ['name' => 'Orange']],
            [4, '4: Lemon', ['name' => 'Lemon']],
        ], $dict2);

        $dict3 = new SimpleDatabaseDictionary(function () use ($conn) {
            return $conn->table('fruits')
                ->select(['name', 'category'])
                ->where('sourness', '<', 4);
        }, [
            'key_field' => 'name',
            'string_key' => true,
            'label' => 'name',
            'search' => ['name', 'category'],
        ]);
        $this->assertDictionary([
            ['Grape', 'Grape', ['category' => 'Vine']],
            ['Banana', 'Banana', ['category' => 'Tropical']],
        ], $dict3);

        $search_result = $dict3->search('tropical');
        $this->assertInstanceOf(DictionarySearchResult::class, $search_result);
        $this->assertEquals([
            'items' => [
                [
                    'key' => 'Banana',
                    'label' => 'Banana',
                    'meta' => ['category' => 'Tropical'],
                ],
            ],
            'paginated' => true,
            'has_more' => false,
            'counts' => [
                'total' => 1,
                'page' => 1,
                'num_per_page' => 10,
                'num_pages' => 1,
            ]
        ], json_decode(json_encode($search_result), true));
    }
}
