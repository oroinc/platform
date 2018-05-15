<?php

namespace Oro\Bundle\TagBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler as BaseSearchHandler;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Autocomplete search handler responsible for searching of Tag entities
 */
class SearchHandler extends BaseSearchHandler
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param string                 $entityName
     * @param array                  $properties
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct($entityName, array $properties, TokenAccessorInterface $tokenAccessor)
    {
        parent::__construct($entityName, $properties);

        $this->tokenAccessor = $tokenAccessor;
    }

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
        $object = $this->entityRepository->findOneBy(
            [
                'name'         => $search,
                'organization' => $this->tokenAccessor->getOrganization()
            ]
        );
        if ($object !== null) {
            $id = $object->getId();
            if (!in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        return [
            'id'     => json_encode(
                [
                    'id'   => $this->getPropertyValue('id', $item),
                    'name' => $this->getPropertyValue('name', $item),
                ]
            ),
            'name'   => $this->getPropertyValue('name', $item)
        ];
    }
}
