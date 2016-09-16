<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchStringFilterType;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

class SearchStringFilter extends AbstractFilter
{
    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return SearchStringFilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new \RuntimeException('Invalid filter datasource adapter provided: ' . get_class($ds));
        }

        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);
        $builder = Criteria::expr();

        switch ($data['type']) {
            case TextFilterType::TYPE_EQUAL:
                $ds->addRestriction($builder->eq($fieldName, $data['value']), FilterUtility::CONDITION_AND);
                return;

            case TextFilterType::TYPE_CONTAINS:
                $ds->addRestriction($builder->contains($fieldName, $data['value']), FilterUtility::CONDITION_AND);
                return;

            case TextFilterType::TYPE_NOT_CONTAINS:
                $ds->addRestriction($builder->notContains($fieldName, $data['value']), FilterUtility::CONDITION_AND);
                return;
        }
    }

    /**
     * Get param or throws exception
     *
     * @param string $paramName
     *
     * @throws \LogicException
     * @return mixed
     */
    protected function get($paramName = null)
    {
        $value = $this->params;

        if ($paramName !== null) {
            if (!isset($this->params[$paramName])) {
                throw new \LogicException(sprintf('Trying to access not existing parameter: "%s"', $paramName));
            }

            $value = $this->params[$paramName];
        }

        return $value;
    }
}
