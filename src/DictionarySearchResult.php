<?php

namespace MadisonSolutions\Dictionary;

use JsonSerializable;

/**
 * @property array $values
 * @property bool $paginated
 * @property ?int $page
 * @property ?int $num_per_page
 * @property ?int $total
 * @property ?int $num_pages
 * @property bool $has_more
 */
class DictionarySearchResult implements JsonSerializable
{
    protected $values;
    protected $paginated;
    protected $page;
    protected $num_per_page;
    protected $total;

    public function __construct()
    {
        $this->values = [];
        $this->setNotPaginated();
    }

    public function setNotPaginated(): DictionarySearchResult
    {
        $this->paginated = false;
        $this->page = null;
        $this->num_per_page = null;
        $this->total = null;
        return $this;
    }

    public function setPaginated(int $page, int $num_per_page, int $total): DictionarySearchResult
    {
        $this->paginated = true;
        $this->page = max(1, $page);
        $this->num_per_page = max(1, $num_per_page);
        $this->total = max(0, $total);
        return $this;
    }

    public function setValues(array $values): DictionarySearchResult
    {
        $this->values = [];
        foreach ($values as $value) {
            $this->appendValue($value);
        }
        return $this;
    }

    public function appendValue(DictionaryValue $value): DictionarySearchResult
    {
        $this->values[] = $value;
        return $this;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'values':
            case 'paginated':
            case 'page':
            case 'num_per_page':
            case 'total':
                return $this->$name;
            case 'num_pages':
                return $this->paginated ? ceil($this->total / $this->num_per_page) : null;
            case 'has_more':
                return $this->paginated ? ($this->page < $this->total) : false;
        }
        return null;
    }

    public function __isset($name): bool
    {
        switch ($name) {
            case 'values':
            case 'paginated':
            case 'page':
            case 'num_per_page':
            case 'total':
            case 'num_pages':
            case 'has_more':
                return true;
        }
        return false;
    }

    public function toArray(): array
    {
        $out = [
            'items' => $this->values,
            'paginated' => $this->paginated,
            'has_more' => $this->has_more,
        ];
        if ($this->paginated) {
            $out['counts'] = [
                'total' => $this->total,
                'page' => $this->page,
                'num_per_page' => $this->num_per_page,
                'num_pages' => $this->num_pages,
            ];
        } else {
            $num = count($this->values);
            $out['counts'] = [
                'total' => $num,
                'page' => 1,
                'num_per_page' => max(1, $num),
                'num_pages' => 1,
            ];
        }
        return $out;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
