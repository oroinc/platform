<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\SearchBundle\Engine\Indexer as SearchIndex;

/**
 * Loads data by SearchBundle query.
 */
class LoadDataBySearchQuery implements ProcessorInterface
{
    /** @var SearchIndex */
    protected $searchIndex;

    /**
     * @param SearchIndex $searchIndex
     */
    public function __construct(SearchIndex $searchIndex)
    {
        $this->searchIndex = $searchIndex;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        if ($context->hasResult()) {
            // result data are already retrieved
            return;
        }

        $query = $context->getQuery();
        if (!$query instanceof SearchQuery) {
            // unsupported query
            return;
        }

        $searchResult = $this->searchIndex->query($query);

        $context->setResult($searchResult->toArray());

        // set callback to be used to calculate total count
        $context->setTotalCountCallback(
            function () use ($searchResult) {
                return $searchResult->getRecordsCount();
            }
        );
    }
}
