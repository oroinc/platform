<?php

namespace Oro\Bundle\CronBundle\Filter;

use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

class CommandWithArgsFilter extends StringFilter
{
    /**
     * {@inheritdoc}
     */
    protected function parseValue($comparisonType, $value)
    {
        if (in_array($comparisonType, [TextFilterType::TYPE_CONTAINS, TextFilterType::TYPE_NOT_CONTAINS], true)) {
            $values = explode(' ', $value);

            return array_map(
                function ($val) use ($comparisonType) {
                    return parent::parseValue($comparisonType, $val);
                },
                $values
            );
        }
        return parent::parseValue($comparisonType, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $type = $data['type'];

        $parameterName = $ds->generateParameterName($this->getName());

        $this->applyFilterToClause(
            $ds,
            $this->buildComparisonExpr(
                $ds,
                $type,
                $this->getDataNameKey($type),
                $parameterName
            )
        );

        if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])) {
            if (is_array($data['value'])) {
                foreach ($data['value'] as $value) {
                    $ds->setParameter($parameterName, $value);
                }
            } else {
                $ds->setParameter($parameterName, $data['value']);
            }
        }

        return true;
    }

    /**
     * Get data name key
     *
     * @param $type
     *
     * @return string
     */
    protected function getDataNameKey($type)
    {
        $data = $this->params['data_name_filter'];

        switch ($type) {
            case TextFilterType::TYPE_CONTAINS:
                $dataName = sprintf('CONCAT(%s)', implode(',', $data['contains']));
                break;
            case TextFilterType::TYPE_NOT_CONTAINS:
                $dataName = sprintf('CONCAT(%s)', implode(',', $data['not_contains']));
                break;
            default:
                $dataName = $this->params[FilterUtility::DATA_NAME_KEY];
        }
        return $dataName;
    }
}
