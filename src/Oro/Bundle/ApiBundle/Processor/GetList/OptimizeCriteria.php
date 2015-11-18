<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Util\Criteria;
use Oro\Bundle\ApiBundle\Util\Join;

class OptimizeCriteria implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

        $criteria = $context->getCriteria();
        $filters  = $context->getFilters();
        foreach ($filters as $filter) {
            if ($filter instanceof ComparisonFilter) {
                $field         = $filter->getField();
                $lastDelimiter = strrpos($field, Criteria::FIELD_DELIMITER);
                if (false !== $lastDelimiter) {
                    $join = $criteria->getJoin(substr($field, 0, $lastDelimiter));
                    if (null !== $join && $join->getJoinType() === Join::LEFT_JOIN) {
                        $join->setJoinType(Join::INNER_JOIN);
                    }
                }
            }
        }
    }
}
