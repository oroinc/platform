<?php

namespace Oro\Bundle\SearchBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\SearchBundle\Api\Repository\SearchEntityRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads entities available for the search API resource.
 */
class LoadSearchEntities implements ProcessorInterface
{
    private SearchEntityRepository $searchEntityRepository;

    public function __construct(SearchEntityRepository $searchEntityRepository)
    {
        $this->searchEntityRepository = $searchEntityRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $entityClasses = $context->getFilterValues()->get('entityType')?->getValue();
        if (\is_string($entityClasses)) {
            $entityClasses = [$entityClasses];
        }
        $context->setResult(
            $this->searchEntityRepository->getSearchEntities(
                $context->getVersion(),
                $context->getRequestType(),
                $entityClasses,
                $context->getFilterValues()->get('searchable')?->getValue()
            )
        );
    }
}
