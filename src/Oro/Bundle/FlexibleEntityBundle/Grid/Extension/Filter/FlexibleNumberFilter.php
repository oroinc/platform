<?php

namespace Oro\Bundle\FlexibleEntityBundle\Grid\Extension\Filter;

use Oro\Bundle\FilterBundle\Filter\NumberFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

class FlexibleNumberFilter extends NumberFilter
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
    public function getOperator($type)
    {
        $type = (int)$type;

        $operatorTypes = array(
            NumberFilterType::TYPE_EQUAL         => '=',
            NumberFilterType::TYPE_GREATER_EQUAL => '>=',
            NumberFilterType::TYPE_GREATER_THAN  => '>',
            NumberFilterType::TYPE_LESS_EQUAL    => '<=',
            NumberFilterType::TYPE_LESS_THAN     => '<',
        );

        return isset($operatorTypes[$type]) ? $operatorTypes[$type] : '=';
    }
}
