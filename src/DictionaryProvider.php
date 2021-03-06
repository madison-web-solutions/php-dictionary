<?php

namespace MadisonSolutions\Dictionary;

class DictionaryProvider
{
    protected $cache;
    protected $getters;

    public function __construct()
    {
        $this->cache = [];
        $this->getters = [];
    }

    public function registerGetter(callable $getter)
    {
        $this->getters[] = $getter;
    }

    public function getDictionary(string $name): ?Dictionary
    {
        $dict = $this->cache[$name] ?? null;
        if ($dict) {
            return $dict;
        }

        foreach ($this->getters as $getter) {
            $dict = $getter($name);
            if ($dict instanceof Dictionary) {
                $this->cache[$name] = $dict;
                return $dict;
            }
        }

        return null;
    }

    public function getSearchableDictionary(string $name): ?Dictionary
    {
        $dict = $this->getDictionary($name);
        if ($dict instanceof SearchableDictionary) {
            return $dict;
        } else {
            return null;
        }
    }
}
