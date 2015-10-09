<?php

namespace Oro\Bundle\FormBundle\EventListener;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;

use Oro\Bundle\BatchBundle\Event\CountQueryOptimizationEvent;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\QueryOptimizationContext;

class CountQueryOptimizationListener
{
    const PRIMARY_CONDITION_PATTERN = '/(?P<alias>\w+).(?P<field>\w+)\s*=\s*(?P<value>true|1|:\w+|\?\d+)/';

    /**
     * @param CountQueryOptimizationEvent $event
     */
    public function onOptimize(CountQueryOptimizationEvent $event)
    {
        $context     = $event->getContext();
        $joinAliases = $event->getOptimizedQueryJoinAliases();
        foreach ($joinAliases as $alias) {
            if ($this->isPrimaryItemLeftJoin($alias, $context)) {
                $event->removeOptimizedQueryJoinAlias($alias);
            }
        }
    }

    /**
     * @param string                   $alias
     * @param QueryOptimizationContext $context
     *
     * @return bool
     */
    protected function isPrimaryItemLeftJoin($alias, QueryOptimizationContext $context)
    {
        $join = $context->getJoinByAlias($alias);
        if ($join->getJoinType() !== Expr\Join::LEFT_JOIN || !$join->getCondition()) {
            return false;
        }

        $className = $context->getEntityClassByAlias($alias);
        if (!is_a($className, 'Oro\Bundle\FormBundle\Entity\PrimaryItem', true)) {
            return false;
        }

        $condition   = trim($join->getCondition());
        $matchResult = preg_match_all(
            self::PRIMARY_CONDITION_PATTERN,
            $condition,
            $matches,
            PREG_SET_ORDER
        );
        if (!$matchResult
            || $matches[0][0] !== $condition
            || $matches[0]['alias'] !== $alias
            || $matches[0]['field'] !== 'primary'
        ) {
            return false;
        }

        return $this->isTrueValue($matches[0]['value'], $context);
    }

    /**
     * @param string                   $value
     * @param QueryOptimizationContext $context
     *
     * @return bool
     */
    protected function isTrueValue($value, QueryOptimizationContext $context)
    {
        if ($value === 'true' || $value === '1') {
            return true;
        }
        if (strpos($value, ':') === 0 || strpos($value, '?') === 0) {
            $param = $context->getOriginalQueryBuilder()->getParameter(substr($value, 1));
            if ($param instanceof Parameter) {
                $paramValue = $param->getValue();
                if ($paramValue === true || $paramValue === 1) {
                    return true;
                }
            }
        }

        return false;
    }
}
