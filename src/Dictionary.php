<?php

namespace MadisonSolutions\Dictionary;

interface Dictionary
{
    /**
     * Attempt to coerce the given key value to the correct type for this dictionary
     *
     * If the $in_key value can be coerced to the correct type for this dictionary, then the coerced
     * value will be stored in the $out_key parameter, and the function will return true.
     * If there is no clear way to coerce the $in_key value then the function will return false.
     *
     * @param $in_key mixed The dictionary key, possibly not in the correct type
     * @param $out_key mixed The dictionary key after coercing to the correct type will be stored here
     * @return bool True if the key was successfully coerced to the correct type, false otherwise
     */
    public function coerceKey($in_key, &$out_key): bool;

    /**
     * Does the given key exist in this dictionary
     *
     * @param mixed $key The key to check
     * @return bool True if the key exists, false otherwise
     */
    public function has($key): bool;

    /**
     * Get the label for the given key
     *
     * Note if the key does not exist null will be returned.
     *
     * @param mixed $key The key to look up
     * @return string|null The label for the given key if it exists, null otherwise
     */
    public function label($key): ?string;

    /**
     * Get some piece of meta-data associated with the given key
     *
     * Note if the key or meta key does not exist, null should be returned
     *
     * @param mixed $key The key to look up
     * @param string $meta_key The key for the particular meta data attribute
     * @return mixed The meta data value
     */
    public function meta($key, string $meta_key);

    /**
     * Get the DictionaryValue for the given key
     *
     * Note if the key does not exist null should be returned.
     *
     * @param mixed $key The key to look up
     * @return DictionaryValue|null The DictionaryValue for the given key if it exists, null otherwise
     */
    public function get($key): ?DictionaryValue;

    /**
     * Get the complete set of DictionaryValue objects for this dictionary
     *
     * @return array Array of DictionaryValue objects one for each entry in this dictionary
     */
    public function all(): array;

    /**
     * Get the complete set of keys for this dictionary
     *
     * @return array Array of keys for each entry in this dictionary
     */
    public function allKeys(): array;
}
