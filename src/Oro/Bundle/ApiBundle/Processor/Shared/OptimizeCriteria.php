<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Collection\Join;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Replaces LEFT JOIN with INNER JOIN in the Criteria object where it is possible.
 */
class OptimizeCriteria implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        $filters  = $context->getFilters();
        foreach ($filters as $filter) {
            if ($filter instanceof ComparisonFilter) {
                $field         = $filter->getField();
                $lastDelimiter = strrpos($field, ConfigUtil::PATH_DELIMITER);
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
