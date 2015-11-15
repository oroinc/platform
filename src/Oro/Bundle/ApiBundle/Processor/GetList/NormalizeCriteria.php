<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class NormalizeCriteria implements ProcessorInterface
{
    const UNLIMITED_RESULT = -1;

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // no Criteria object
            return;
        }

        // check if a paging disabled
        if (self::UNLIMITED_RESULT === $criteria->getMaxResults()) {
            $criteria->setFirstResult(null);
            $criteria->setMaxResults(null);
        }
    }
}
