<?php

namespace MadisonSolutions\Dictionary;

use MadisonSolutions\Coerce\Coerce;

abstract class StaticDictionary implements Dictionary
{
    protected $cached_defns;

    public function __construct()
    {
        $this->cached_defns = $this->defns();
    }

    abstract protected function defns(): array;

    public function coerceKey($in_key, &$out_key) : bool
    {
        return Coerce::toString($in_key, $out_key);
    }

    public function has($key): bool
    {
        if (! $this->coerceKey($key, $coerced_key)) {
            return false;
        }
        return array_key_exists($coerced_key, $this->cached_defns);
    }

    protected function getItem($key, &$item, &$coerced_key): bool
    {
        if (! $this->coerceKey($key, $coerced_key)) {
            return false;
        }
        if (array_key_exists($coerced_key, $this->cached_defns)) {
            $item = $this->cached_defns[$coerced_key];
            return true;
        } else {
            return false;
        }
    }

    protected function labelFromItem(array $item, string $coerced_key): string
    {
        return $item['label'] ?? mb_convert_case($coerced_key, MB_CASE_TITLE, 'UTF-8');
    }

    public function label($key): ?string
    {
        if ($this->getItem($key, $item, $coerced_key)) {
            return $this->labelFromItem($item, $coerced_key);
        } else {
            return null;
        }
    }

    protected function metaFromItem(array $item): array
    {
        unset($item['label']);
        return $item;
    }

    public function meta($key, string $meta_key)
    {
        if ($this->getItem($key, $item, $coerced_key)) {
            return $this->metaFromItem($item)[$meta_key];
        } else {
            return null;
        }
    }

    public function get($key): ?DictionaryValue
    {
        if ($this->getItem($key, $item, $coerced_key)) {
            return new DictionaryValue($coerced_key, $this->labelFromItem($item, $coerced_key), $this->metaFromItem($item));
        } else {
            return null;
        }
    }

    public function all(): array
    {
        $out = [];
        foreach ($this->cached_defns as $key => $item) {
            $this->coerceKey($key, $coerced_key);
            $out[] = new DictionaryValue($coerced_key, $this->labelFromItem($item, $coerced_key), $this->metaFromItem($item));
        }
        return $out;
    }

    public function allKeys(): array
    {
        return array_keys($this->cached_defns);
    }
}
