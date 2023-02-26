<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Component\Exception\UnexpectedTypeException;

/**
 * The filter by a numeric value for a datasource based on a search index.
 */
class SearchNumberFilter extends AbstractFilter
{
    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, SearchFilterDatasourceAdapter::class);
        }

        return $this->applyRestrictions($ds, $this->parseData($data));
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param array                            $data
     *
     * @return bool
     */
    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $data)
    {
        $value = $data['value'];
        $builder = Criteria::expr();
        $fieldName = $this->getFieldName($data);

        $result = true;
        switch ($data['type']) {
            case NumberFilterType::TYPE_GREATER_EQUAL:
                $ds->addRestriction($builder->gte($fieldName, $value), FilterUtility::CONDITION_AND);
                break;
            case NumberFilterType::TYPE_GREATER_THAN:
                $ds->addRestriction($builder->gt($fieldName, $value), FilterUtility::CONDITION_AND);
                break;
            case NumberFilterType::TYPE_EQUAL:
                $ds->addRestriction($builder->eq($fieldName, $value), FilterUtility::CONDITION_AND);
                break;
            case NumberFilterType::TYPE_NOT_EQUAL:
                $ds->addRestriction($builder->neq($fieldName, $value), FilterUtility::CONDITION_AND);
                break;
            case NumberFilterType::TYPE_LESS_EQUAL:
                $ds->addRestriction($builder->lte($fieldName, $value), FilterUtility::CONDITION_AND);
                break;
            case NumberFilterType::TYPE_LESS_THAN:
                $ds->addRestriction($builder->lt($fieldName, $value), FilterUtility::CONDITION_AND);
                break;
            case FilterUtility::TYPE_EMPTY:
                $ds->addRestriction($builder->notExists($fieldName), FilterUtility::CONDITION_AND);
                break;
            case FilterUtility::TYPE_NOT_EMPTY:
                $ds->addRestriction($builder->exists($fieldName), FilterUtility::CONDITION_AND);
                break;
            default:
                $result = false;
                break;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        $formView = $this->getFormView();
        $metadata['formatterOptions'] = $formView->vars['formatter_options'];
        $metadata['arraySeparator'] = $formView->vars['array_separator'];
        $metadata['arrayOperators'] = $formView->vars['array_operators'];
        $metadata['dataType'] = $formView->vars['data_type'];

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType(): string
    {
        return NumberFilterType::class;
    }

    protected function getFieldName(array $data): string
    {
        return $this->get(FilterUtility::DATA_NAME_KEY);
    }
}
