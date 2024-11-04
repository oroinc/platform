<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;

/**
 * The filter by predefined values that allows to select only one item from a choice list.
 */
class SingleChoiceFilter extends ChoiceFilter
{
    #[\Override]
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName,
        $parameterName
    ) {
        switch ($comparisonType) {
            case ChoiceFilterType::TYPE_NOT_CONTAINS:
                return $ds->expr()->neq($fieldName, $parameterName, true);
            default:
                return $ds->expr()->eq($fieldName, $parameterName, true);
        }
    }

    #[\Override]
    public function prepareData(array $data): array
    {
        if (isset($data['value']) && !$this->getOr('keep_string_value', false) && is_numeric($data['value'])) {
            $data['value'] = (int)$data['value'];
        }

        return $data;
    }

    #[\Override]
    protected function parseValue(array $data)
    {
        if (!isset($data['value']) || !$data['value']) {
            return false;
        }

        return $data;
    }
}
