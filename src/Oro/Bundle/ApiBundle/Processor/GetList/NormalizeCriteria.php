<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class NormalizeCriteria implements ProcessorInterface
{
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
            // no the Criteria object
            return;
        }

        // check if a paging disabled
        if (-1 === $criteria->getMaxResults()) {
            $criteria->setFirstResult(null);
            $criteria->setMaxResults(null);
        }
    }
}
