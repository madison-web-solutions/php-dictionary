<?php

namespace MadisonSolutions\Dictionary;

use JsonSerializable;

/**
 * @property $key
 * @property string $label
 * @property array $meta
 */
class DictionaryValue implements JsonSerializable
{
    protected $key;
    protected $label;
    protected $meta;

    public function __construct($key, string $label, array $meta)
    {
        $this->key = $key;
        $this->label = $label;
        $this->meta = $meta;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'key':
            case 'label':
            case 'meta':
                return $this->$name;
        }
        return $this->meta[$name] ?? null;
    }

    public function __isset($name): bool
    {
        switch ($name) {
            case 'key':
            case 'label':
            case 'meta':
                return true;
        }
        return $this->hasMeta($name);
    }

    public function hasMeta(string $meta_key): bool
    {
        return array_key_exists($meta_key, $this->meta);
    }

    /**
     * Get some piece of meta-data associated with this DictionaryValue
     *
     * Note if the meta key does not exist, null will be returned
     *
     * @param string $meta_key The key for the particular meta data attribute
     * @return mixed The meta data value
     */
    public function meta(string $meta_key)
    {
        return $this->meta[$meta_key] ?? null;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'meta' => $this->meta,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
