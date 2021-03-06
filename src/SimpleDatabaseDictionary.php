<?php

namespace MadisonSolutions\Dictionary;

use stdClass;
use Illuminate\Database\Query\Builder as QueryBuilder;
use MadisonSolutions\Coerce\Coerce;

class SimpleDatabaseDictionary extends DatabaseDictionary
{
    protected $get_base_query_fn;
    protected $label_fn;
    protected $search_fn;
    protected $search_fields;

    public function __construct(callable $get_base_query_fn, array $opts)
    {
        parent::__construct();
        $this->get_base_query_fn = $get_base_query_fn;
        $this->key_field = Coerce::toStringOrFail($opts['key_field'] ?? 'id');
        $this->string_key = Coerce::toBoolOrFail($opts['string_key'] ?? false);
        $this->num_per_page = max(1, Coerce::toIntOrFail($opts['num_per_page'] ?? 10));
        if (is_callable($opts['label'] ?? null)) {
            $this->label_fn = $opts['label'];
        } else {
            $this->label_field = Coerce::toStringOrFail($opts['label'] ?? 'label');
        }
        if (is_callable($opts['search'] ?? null)) {
            $this->search_fn = $opts['search'];
        } elseif (is_array($opts['search'] ?? null)) {
            $this->search_fields = $opts['search'];
        } else {
            $this->search_fields = [Coerce::toStringOrFail($opts['search'] ?? $this->label_field ?? 'label')];
        }
    }

    protected function getBaseQuery(): QueryBuilder
    {
        return call_user_func($this->get_base_query_fn);
    }

    protected function labelFromRow(stdClass $row): string
    {
        if ($this->label_fn) {
            return call_user_func($this->label_fn, $row);
        } else {
            return (string) $row->{$this->label_field};
        }
    }

    protected function applySearchCriteria(QueryBuilder $query, string $search_text, array $opts): void
    {
        if ($this->search_fn) {
            call_user_func($this->search_fn, $query, $search_text, $opts);
        } else {
            DatabaseDictionary::addLikeSearchToQuery($query, $this->search_fields, $search_text);
        }
    }
}
