<?php

namespace Oro\Bundle\ApiBundle\Processor\DeleteList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Limits number of records should be deleted to 100
 */
class SetDeleteLimit implements ProcessorInterface
{
    const DELETE_LIMIT = 100;

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

        if ($criteria->getMaxResults()) {
            // max results already set
            return;
        }

        $criteria->setMaxResults(self::DELETE_LIMIT);
    }
}
