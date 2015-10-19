<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class SetDefaultPaging implements ProcessorInterface
{
    const MAX_RESULTS = 10;

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
            $criteria = new Criteria();
            $context->setCriteria($criteria);
        }

        if (null === $criteria->getMaxResults()) {
            $criteria->setMaxResults(static::MAX_RESULTS);
        }
    }
}
