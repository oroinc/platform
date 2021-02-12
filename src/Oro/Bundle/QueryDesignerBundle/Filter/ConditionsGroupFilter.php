<?php

namespace Oro\Bundle\QueryDesignerBundle\Filter;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilder;
use Oro\Component\DoctrineUtils\ORM\DqlUtil;

/**
 * Apply filters within conditions group
 */
class ConditionsGroupFilter extends AbstractFilter
{
    /**
     * @var RestrictionBuilder
     */
    private $restrictionBuilder;

    /**
     * @var array
     */
    private $knownAliases = [];

    /**
     * @param RestrictionBuilder $restrictionBuilder
     */
    public function __construct(RestrictionBuilder $restrictionBuilder)
    {
        $this->restrictionBuilder = $restrictionBuilder;
        $this->name = 'conditions_group';
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof GroupingOrmFilterDatasourceAdapter) {
            throw new \InvalidArgumentException(\sprintf(
                'Unexpected type of data source. Expected "%s" got "%s',
                GroupingOrmFilterDatasourceAdapter::class,
                \get_class($ds)
            ));
        }

        $filters = $data['filters'];
        $filters['in_group'] = true;

        $this->rememberAliases($ds);
        $computedFilterExpression = null;
        if ($this->hasRelationsInFilters($ds, $filters)) {
            $qb = $ds->getQueryBuilder();
            $fieldsExprs = $this->createConditionFieldExprs($qb);
            [$subDql, $boundParameters] = $this->getSubQueryExpressionWithParameters($ds, $fieldsExprs[0], $filters);
            $filterExpression = $qb->expr()->exists($subDql);
        } else {
            $subQb = $this->createSubQueryBuilder($ds, $filters);
            $boundParameters = $subQb->getParameters();
            $filterExpression = (string)$subQb->getDQLPart('where');
            $computedFilterExpression = (string)$subQb->getDQLPart('having');
        }

        if ($filterExpression) {
            $this->applyFilterToClause($ds, $filterExpression);
        }
        if ($computedFilterExpression) {
            $ds->addRestriction($computedFilterExpression, FilterUtility::CONDITION_AND, true);
        }
        $this->applyParameters($ds, $boundParameters);
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param QueryBuilder $qb
     *
     * @return [$dql, $replacedAliases]
     */
    protected function createDQLWithReplacedAliases(FilterDatasourceAdapterInterface $ds, QueryBuilder $qb)
    {
        $replacements = [];
        foreach (DqlUtil::getAliases($qb->getDQL()) as $alias) {
            if (!array_key_exists($alias, $this->knownAliases)) {
                $this->knownAliases[$alias] = 0;
            } else {
                $replacement = $alias . '_' . ++$this->knownAliases[$alias];
                $replacements[] = [$alias, $replacement];
            }
        }

        if ($replacements) {
            return [
                DqlUtil::replaceAliases($qb->getDQL(), $replacements),
                array_combine(array_column($replacements, 0), array_column($replacements, 1))
            ];
        }

        return [$qb->getDQL(), $qb->getParameters()];
    }

    /**
     * {@inheritDoc}
     */
    protected function createSubQueryBuilder(OrmFilterDatasourceAdapter $ds, $filter = null): QueryBuilder
    {
        $qb = clone $ds->getQueryBuilder();
        $qb->resetDQLPart('where');
        $dataSourceClass = get_class($ds);
        /** @var OrmFilterDatasourceAdapter $newDs */
        $newDs = new $dataSourceClass($qb);
        $this->restrictionBuilder->buildRestrictions($filter, $newDs);

        return $newDs->getQueryBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        return null;
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param array $filters
     * @return bool
     */
    private function hasRelationsInFilters(FilterDatasourceAdapterInterface $ds, array $filters): bool
    {
        foreach ($filters as $filter) {
            if (!empty($filter['column']) && $this->findRelatedJoinByColumn($ds, $filter['column'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param Collection|array|Parameter[] $boundParameters
     */
    private function applyParameters(OrmFilterDatasourceAdapter $ds, $boundParameters)
    {
        foreach ($boundParameters as $parameter) {
            $ds->getQueryBuilder()->setParameter(
                $parameter->getName(),
                $parameter->getValue(),
                $parameter->typeWasSpecified() ? $parameter->getType() : null
            );
        }
    }

    /**
     * @param GroupingOrmFilterDatasourceAdapter $ds
     */
    private function rememberAliases(GroupingOrmFilterDatasourceAdapter $ds): void
    {
        $this->knownAliases = [];
        foreach (DqlUtil::getAliases($ds->getQueryBuilder()->getDQL()) as $alias) {
            $this->knownAliases[$alias] = 0;
        }
    }
}
