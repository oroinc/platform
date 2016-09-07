<?php

namespace Oro\Bundle\SearchBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\Search\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Query\Query;

class SearchStringFilter extends AbstractFilter
{
    /**
     * @return string
     */
    protected function getFormType()
    {
        return TextFilterType::NAME;
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param mixed $data
     * @return bool|void
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);

        if ($ds instanceof SearchFilterDatasourceAdapter) {
            switch ($data['type']) {
                case TextFilterType::TYPE_EQUAL:
                    $ds->getWrappedSearchQuery()->andWhere($fieldName, Query::OPERATOR_EQUALS, $data['value']);
                    return;
                case TextFilterType::TYPE_NOT_EQUAL:
                    $ds->getWrappedSearchQuery()->andWhere($fieldName, Query::OPERATOR_EQUALS, $data['value']);
                    return;
                case TextFilterType::TYPE_CONTAINS:
                    $ds->getWrappedSearchQuery()->andWhere($fieldName, Query::OPERATOR_CONTAINS, $data['value']);
                    return;
                case TextFilterType::TYPE_NOT_CONTAINS:
                    $ds->getWrappedSearchQuery()->andWhere($fieldName, Query::OPERATOR_NOT_CONTAINS, $data['value']);
                    return;
            }
        }

        throw new \RuntimeException('Invalid filter datasource adapter provided to '.self::class);
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
