<?php

namespace MadisonSolutions\Dictionary;

use MadisonSolutions\Coerce\Coerce;

class SimpleStaticDictionary extends StaticDictionary
{
    public static function fromKeys(array $keys): SimpleStaticDictionary
    {
        $defns = [];
        foreach ($keys as $key) {
            $coerced_key = Coerce::toStringOrFail($key);
            $defns[$coerced_key] = [
                'label' => mb_convert_case($coerced_key, MB_CASE_TITLE, 'UTF-8'),
            ];
        }
        return new SimpleStaticDictionary($defns);
    }

    public static function fromKeysAndLabels(array $keys_and_labels): SimpleStaticDictionary
    {
        $defns = [];
        foreach ($keys_and_labels as $key => $label) {
            $coerced_key = Coerce::toStringOrFail($key);
            $defns[$coerced_key] = [
                'label' => Coerce::toStringOrFail($label),
            ];
        }
        return new SimpleStaticDictionary($defns);
    }

    public function __construct(array $defns)
    {
        $this->cached_defns = $defns;
        parent::__construct();
    }

    protected function defns(): array
    {
        return $this->cached_defns;
    }
}
