<?php

namespace Oro\Bundle\FlexibleEntityBundle\Grid\Extension\Filter;

use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

class FlexibleStringFilter extends StringFilter
{
    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $operator = $this->getOperator($data['type']);

        $fen = $this->get(FilterUtility::FEN_KEY);
        $this->util->applyFlexibleFilter(
            $ds,
            $fen,
            $this->get(FilterUtility::DATA_NAME_KEY),
            $data['value'],
            $operator
        );

        return true;
    }

    /**
     * Get operator string
     *
     * @param int $type
     *
     * @return string
     */
    protected function getOperator($type)
    {
        $type = (int)$type;

        $operatorTypes = array(
            TextFilterType::TYPE_CONTAINS     => 'LIKE',
            TextFilterType::TYPE_NOT_CONTAINS => 'NOT LIKE',
            TextFilterType::TYPE_EQUAL        => '=',
            TextFilterType::TYPE_STARTS_WITH  => 'LIKE',
            TextFilterType::TYPE_ENDS_WITH    => 'LIKE',
        );

        return isset($operatorTypes[$type]) ? $operatorTypes[$type] : 'LIKE';
    }
}
