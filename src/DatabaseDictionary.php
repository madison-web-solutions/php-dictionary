<?php

namespace MadisonSolutions\Dictionary;

use stdClass;
use Illuminate\Database\Query\Builder as QueryBuilder;
use MadisonSolutions\Coerce\Coerce;

abstract class DatabaseDictionary implements SearchableDictionary
{
    protected $cache;

    protected $key_field = 'id';
    protected $label_field = 'label';
    protected $num_per_page = 10; // can also set to false to not paginate
    protected $string_key = false;

    public function __construct()
    {
        $this->cache = [];
    }

    public function coerceKey($in_key, &$out_key): bool
    {
        if ($this->string_key) {
            return Coerce::toString($in_key, $out_key);
        } else {
            return Coerce::toInt($in_key, $out_key);
        }
    }

    abstract protected function getBaseQuery(): QueryBuilder;

    protected function getKeysQuery(): QueryBuilder
    {
        return $this->getBaseQuery();
    }

    protected function keyFromRow(stdClass $row)
    {
        $key = $row->{$this->key_field};
        if ($this->coerceKey($key, $coerced_key)) {
            return $coerced_key;
        }
        return $key;
    }

    protected function labelFromRow(stdClass $row): string
    {
        return (string) $row->{$this->label_field};
    }

    protected function metaFromRow(stdClass $row): array
    {
        $meta = (array) $row;
        unset($meta[$this->key_field]);
        unset($meta[$this->label_field]);
        return $meta;
    }

    protected function applyKeyCriteria($query, $coerced_key): void
    {
        $query->where($this->key_field, $coerced_key);
    }

    protected static function addLikeSearchToQuery(QueryBuilder $query, array $search_fields, string $search_text): void
    {
        $search_terms = array_map(function ($word) {
            return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $word) . '%';
        }, explode(' ', $search_text));

        foreach ($search_terms as $term) {
            $query->where(function ($q) use ($search_fields, $term) {
                foreach ($search_fields as $i => $search_field) {
                    if ($i == 0) {
                        $q->where($search_field, 'like', $term);
                    } else {
                        $q->orWhere($search_field, 'like', $term);
                    }
                }
            });
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function applySearchCriteria(QueryBuilder $query, string $search_text, array $opts): void
    {
        DatabaseDictionary::addLikeSearchToQuery($query, [$this->label_field], $search_text);
    }

    protected function getRow($coerced_key): ?stdClass
    {
        $query = $this->getBaseQuery();
        $this->applyKeyCriteria($query, $coerced_key);
        return $query->first();
    }

    public function has($key): bool
    {
        return $this->get($key) ? true : false;
    }

    public function label($key): ?string
    {
        $value = $this->get($key);
        return $value ? $value->label : null;
    }

    public function meta($key, string $meta_key)
    {
        $value = $this->get($key);
        return $value ? $value->meta($meta_key) : null;
    }

    protected function valueFromRow($coerced_key, ?stdClass $row): ?DictionaryValue
    {
        if (! $row) {
            $this->cache[$coerced_key] = null;
            return null;
        }
        $value = new DictionaryValue($coerced_key, $this->labelFromRow($row), $this->metaFromRow($row));
        $this->cache[$coerced_key] = $value;
        return $value;
    }

    public function get($key): ?DictionaryValue
    {
        if (! $this->coerceKey($key, $coerced_key)) {
            return null;
        }
        if (array_key_exists($coerced_key, $this->cache)) {
            return $this->cache[$coerced_key];
        } else {
            return $this->valueFromRow($coerced_key, $this->getRow($coerced_key));
        }
    }

    public function all(): array
    {
        $values = [];
        $query = $this->getBaseQuery();
        foreach ($query->get() as $row) {
            $coerced_key = $this->keyFromRow($row);
            $values[] = $this->valueFromRow($coerced_key, $row);
        }
        return $values;
    }

    public function allKeys(): array
    {
        $keys = [];
        $query = $this->getKeysQuery();
        foreach ($query->get() as $row) {
            $keys[] = $this->keyFromRow($row);
        }
        return $keys;
    }

    public function search(string $search_text, array $opts = []): DictionarySearchResult
    {
        $query = $this->getBaseQuery();
        $this->applySearchCriteria($query, $search_text, $opts);
        $result = new DictionarySearchResult();
        if ($this->num_per_page > 0) {
            if (Coerce::toInt($opts['page'] ?? null, $page)) {
                $page = min(1, $page);
            } else {
                $page = 1;
            }
            $total = (clone $query)->count();
            $result->setPaginated($page, $this->num_per_page, $total);
            $query->limit($this->num_per_page)->offset(($page - 1) * $this->num_per_page);
        }
        foreach ($query->get() as $row) {
            $coerced_key = $this->keyFromRow($row);
            $result->appendValue($this->valueFromRow($coerced_key, $row));
        }
        return $result;
    }

    public function forget($key): void
    {
        if ($this->coerceKey($key, $coerced_key)) {
            unset($this->cache[$coerced_key]);
        }
    }

    public function forgetAll(): void
    {
        $this->cache = [];
    }
}
