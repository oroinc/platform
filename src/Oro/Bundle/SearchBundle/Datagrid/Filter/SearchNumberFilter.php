<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Exception\LogicException;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

/**
 * Basic number filter which supports multiple operators, for "search" datasource.
 */
class SearchNumberFilter extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new \RuntimeException('Invalid filter datasource adapter provided: '.get_class($ds));
        }

        return $this->applyRestrictions($ds, $this->parseData($data));
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param array $data
     * @return bool
     */
    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $data)
    {
        $value = $data['value'];
        $builder = Criteria::expr();
        $fieldName = $this->getFieldName($data);

        switch ($data['type']) {
            case NumberFilterType::TYPE_GREATER_EQUAL:
                $ds->addRestriction($builder->gte($fieldName, $value), FilterUtility::CONDITION_AND);

                return true;

            case NumberFilterType::TYPE_GREATER_THAN:
                $ds->addRestriction($builder->gt($fieldName, $value), FilterUtility::CONDITION_AND);

                return true;

            case NumberFilterType::TYPE_EQUAL:
                $ds->addRestriction($builder->eq($fieldName, $value), FilterUtility::CONDITION_AND);

                return true;

            case NumberFilterType::TYPE_NOT_EQUAL:
                $ds->addRestriction($builder->neq($fieldName, $value), FilterUtility::CONDITION_AND);

                return true;

            case NumberFilterType::TYPE_LESS_EQUAL:
                $ds->addRestriction($builder->lte($fieldName, $value), FilterUtility::CONDITION_AND);

                return true;

            case NumberFilterType::TYPE_LESS_THAN:
                $ds->addRestriction($builder->lt($fieldName, $value), FilterUtility::CONDITION_AND);

                return true;

            case FilterUtility::TYPE_EMPTY:
                $ds->addRestriction($builder->notExists($fieldName), FilterUtility::CONDITION_AND);

                return true;

            case FilterUtility::TYPE_NOT_EMPTY:
                $ds->addRestriction($builder->exists($fieldName), FilterUtility::CONDITION_AND);

                return true;
        }

        return false;
    }

    /**
     * @param array $data
     * @return string
     */
    protected function getFieldName(array $data)
    {
        return $this->get(FilterUtility::DATA_NAME_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        $formView = $this->getForm()->createView();
        $metadata['formatterOptions'] = $formView->vars['formatter_options'];
        $metadata['arraySeparator'] = $formView->vars['array_separator'];
        $metadata['arrayOperators'] = $formView->vars['array_operators'];
        $metadata['dataType'] = $formView->vars['data_type'];

        return $metadata;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return NumberFilterType::class;
    }
}
