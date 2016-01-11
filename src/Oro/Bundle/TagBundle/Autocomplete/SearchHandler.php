<?php

namespace Oro\Bundle\TagBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler as BaseSearchHandler;

class SearchHandler extends BaseSearchHandler
{
    /**
     * @param string $search
     * @param int    $firstResult
     * @param int    $maxResults
     *
     * @return array
     */
    protected function searchIds($search, $firstResult, $maxResults)
    {
        $ids = parent::searchIds($search, $firstResult, $maxResults);
        // Need to make additional query cause of Mysql Full-Text Search limitation and databases stop words.
        // http://dev.mysql.com/doc/refman/5.0/en/server-system-variables.html#sysvar_ft_min_word_len
        // http://dev.mysql.com/doc/refman/5.0/en/fulltext-stopwords.html
        // http://www.postgresql.org/docs/9.1/static/textsearch-dictionaries.html
        if (empty($ids)) {
            $object = $this->entityRepository->findOneBy(['name' => $search]);
            if ($object !== null) {
                $ids[] = $object->getId();
            }
        }

        return $ids;
    }
}
