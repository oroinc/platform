<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the maximum number of entities that can be deleted by one request. By default 100.
 */
class SetDeleteLimit implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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

        if (null === $criteria->getMaxResults()) {
            $limit = $context->getConfig()->getMaxResults();
            if (null === $limit) {
                $limit = $this->getDefaultDeleteLimit();
            }
            $criteria->setMaxResults($limit);
        }
    }

    /**
     * @return int
     */
    protected function getDefaultDeleteLimit()
    {
        return 100;
    }
}
