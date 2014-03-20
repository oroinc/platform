<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter;

class RestrictionBuilder implements RestrictionBuilderInterface
{
    /** @var Manager */
    protected $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRestrictions(array $filters, GroupingOrmFilterDatasourceAdapter $ds)
    {
        $this->doBuildRestrictions($filters, $ds);
        $ds->applyRestrictions();
    }

    /**
     * Recursive iterates through filters and builds an expression to be applied to the given data source
     *
     * @param array                              $filters
     * @param GroupingOrmFilterDatasourceAdapter $ds
     */
    protected function doBuildRestrictions(array $filters, GroupingOrmFilterDatasourceAdapter $ds)
    {
        $operatorStack = [FilterUtility::CONDITION_AND];
        foreach ($filters as $item) {
            if (is_string($item)) {
                array_push($operatorStack, $item);
            } elseif (!isset($item['filter'])) {
                $ds->beginRestrictionGroup(array_pop($operatorStack));
                $this->doBuildRestrictions($item, $ds);
                $ds->endRestrictionGroup();
            } else {
                $operator = array_pop($operatorStack);

                /** @var FilterInterface $filter */
                $filter = $this->getFilterObject($item['filter'], $item['column']);

                $form = $filter->getForm();
                if (!$form->isSubmitted()) {
                    $form->submit($item['filterData']);
                }
                if ($form->isValid()) {
                    $ds->beginRestrictionGroup($operator);
                    $filter->apply($ds, $form->getData());
                    $ds->endRestrictionGroup();
                }
            }
        }
    }

    /**
     * Returns prepared filter object.
     *
     * @param string $name       A filter name.
     * @param string $columnName A column name this filter should be applied.
     * @param string $operator   A filter operator. Can be "OR" or "AND".
     *
     * @return FilterInterface
     */
    protected function getFilterObject($name, $columnName, $operator = null)
    {
        $params = [
            FilterUtility::DATA_NAME_KEY => $columnName
        ];
        if ($operator !== null) {
            $params[FilterUtility::CONDITION_KEY] = $operator;
        }

        return $this->manager->createFilter($name, $params);
    }
}
