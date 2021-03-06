<?php

namespace MadisonSolutions\Dictionary;

interface SearchableDictionary extends Dictionary
{
    /**
     * Search the dictionary using the given string of text
     *
     * @param string $search_text The text to search for
     * @param array $opts Optional array of additional options
     * @return DictionarySearchResult The search results
     */
    public function search(string $search_text, array $opts = []): DictionarySearchResult;
}
