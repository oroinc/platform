<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the maximum number of entities that can be deleted by one request.
 */
class SetDeleteLimit implements ProcessorInterface
{
    private int $maxDeleteEntitiesLimit;

    public function __construct(int $maxDeleteEntitiesLimit)
    {
        $this->maxDeleteEntitiesLimit = $maxDeleteEntitiesLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var DeleteListContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        if (null !== $criteria->getMaxResults()) {
            // the limit is already set
            return;
        }

        $maxDeleteEntitiesLimit = $this->getMaxDeleteEntitiesLimit($context->getConfig());
        if ($maxDeleteEntitiesLimit > 0) {
            $criteria->setMaxResults($maxDeleteEntitiesLimit);
        }
    }

    private function getMaxDeleteEntitiesLimit(?EntityDefinitionConfig $config): int
    {
        if (null === $config) {
            return $this->maxDeleteEntitiesLimit;
        }

        return $config->getMaxResults() ?? $this->maxDeleteEntitiesLimit;
    }
}
