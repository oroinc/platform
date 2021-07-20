<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Filter\FilterOrmQueryUtil;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;

/**
 * Provides a functionality to apply filters within a conditions group.
 */
class ConditionsGroupBuilder
{
    /**
     * @var int
     */
    private $groupLevel = 0;

    public function apply(
        RestrictionBuilder $restrictionBuilder,
        GroupingOrmFilterDatasourceAdapter $ds,
        array $filters
    ): void {
        $filters['in_group'] = true;

        $computedFilterExpression = null;
        if ($this->hasRelationsInFilters($ds, $filters)) {
            $qb = $ds->getQueryBuilder();
            [$subDql, $boundParameters] = FilterOrmQueryUtil::getSubQueryExpressionWithParameters(
                $ds,
                $this->createSubQueryBuilder($restrictionBuilder, $ds, $filters),
                FilterOrmQueryUtil::getSingleIdentifierFieldExpr($qb),
                'l_' . ++$this->groupLevel . '_conditions_group'
            );
            $filterExpression = $qb->expr()->exists($subDql);
        } else {
            $subQb = $this->createSubQueryBuilder($restrictionBuilder, $ds, $filters);
            $boundParameters = $subQb->getParameters();
            $filterExpression = (string)$subQb->getDQLPart('where');
            $computedFilterExpression = (string)$subQb->getDQLPart('having');
        }

        if ($filterExpression) {
            $ds->addRestriction($filterExpression, FilterUtility::CONDITION_AND);
        }
        if ($computedFilterExpression) {
            $ds->addRestriction($computedFilterExpression, FilterUtility::CONDITION_AND, true);
        }
        $this->applyParameters($ds, $boundParameters);
    }

    private function createSubQueryBuilder(
        RestrictionBuilder $restrictionBuilder,
        GroupingOrmFilterDatasourceAdapter $ds,
        array $filters
    ): QueryBuilder {
        $qb = clone $ds->getQueryBuilder();
        $qb->resetDQLPart('where');
        $dataSourceClass = \get_class($ds);
        $newDs = new $dataSourceClass($qb);
        $restrictionBuilder->buildRestrictions($filters, $newDs);

        return $newDs->getQueryBuilder();
    }

    private function hasRelationsInFilters(GroupingOrmFilterDatasourceAdapter $ds, array $filters): bool
    {
        foreach ($filters as $filter) {
            if (!empty($filter['column']) && FilterOrmQueryUtil::findRelatedJoinByColumn($ds, $filter['column'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param GroupingOrmFilterDatasourceAdapter $ds
     * @param Collection|Parameter[]             $boundParameters
     */
    private function applyParameters(GroupingOrmFilterDatasourceAdapter $ds, Collection $boundParameters): void
    {
        foreach ($boundParameters as $parameter) {
            $ds->getQueryBuilder()->setParameter(
                $parameter->getName(),
                $parameter->getValue(),
                $parameter->typeWasSpecified() ? $parameter->getType() : null
            );
        }
    }
}
