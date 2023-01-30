<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Removes paging properties (FirstResult and MaxResults) from the Criteria object
 * if MaxResults equals to -1, that means "unlimited".
 * Sets the maximum number of entities that can be retrieved by a request.
 */
class NormalizePaging implements ProcessorInterface
{
    private int $maxEntitiesLimit;

    public function __construct(int $maxEntitiesLimit)
    {
        $this->maxEntitiesLimit = $maxEntitiesLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        if (-1 === $criteria->getMaxResults()) {
            // a paging is disabled or unlimited page size is requested
            $criteria->setFirstResult(null);
            $criteria->setMaxResults(null);
        }

        // apply the configured max results limit
        $maxResultsLimit = $this->getMaxResultsLimit($context->getConfig());
        if ($maxResultsLimit > 0 && $criteria->getMaxResults() > $maxResultsLimit) {
            $criteria->setMaxResults($maxResultsLimit);
        }
    }

    private function getMaxResultsLimit(?EntityDefinitionConfig $config): int
    {
        if (null === $config) {
            return $this->maxEntitiesLimit;
        }

        return $config->getMaxResults() ?? $this->maxEntitiesLimit;
    }
}
