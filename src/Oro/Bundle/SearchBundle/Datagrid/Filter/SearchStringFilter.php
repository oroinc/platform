<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchStringFilterType;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

class SearchStringFilter extends AbstractFilter
{
    /**
     * @return string
     */
    protected function getFormType()
    {
        return SearchStringFilterType::NAME;
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param mixed                            $data
     * @return bool|void
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);

        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new \RuntimeException('Invalid filter datasource adapter provided: ' . get_class($ds));
        }

        switch ($data['type']) {
            case SearchStringFilterType::TYPE_EQUAL:
                $ds->getWrappedSearchQuery()
                    ->getCriteria()
                    ->andWhere(Criteria::expr()->eq($fieldName, $data['value']));

                return;
            case SearchStringFilterType::TYPE_CONTAINS:
                $ds->getWrappedSearchQuery()
                    ->getCriteria()
                    ->andWhere(Criteria::expr()->contains($fieldName, $data['value']));

                return;
            case SearchStringFilterType::TYPE_NOT_CONTAINS:
                $ds->getWrappedSearchQuery()
                    ->getCriteria()
                    ->andWhere(Criteria::expr()->notContains($fieldName, $data['value']));

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
