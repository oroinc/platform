<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\SelectRowFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

class SelectRowFilter extends ChoiceFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return SelectRowFilterType::NAME;
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

        if ($data['in'] !== null) {
            if (!empty($data['in'])) {
                $parameterName = $ds->generateParameterName($this->getName());
                $this->applyFilterToClause(
                    $ds,
                    $ds->expr()->in($this->get(FilterUtility::DATA_NAME_KEY), $parameterName, true)
                );
                $ds->setParameter($parameterName, $data['in']);
            } else {
                // requested to return all selected rows, but no one row are selected
                $this->applyFilterToClause($ds, $ds->expr()->eq(0, 1));
            }
        } elseif ($data['out'] !== null && !empty($data['out'])) {
            $parameterName = $ds->generateParameterName($this->getName());
            $this->applyFilterToClause(
                $ds,
                $ds->expr()->notIn($this->get(FilterUtility::DATA_NAME_KEY), $parameterName, true)
            );
            $ds->setParameter($parameterName, $data['out']);
        }

        return true;
    }

    /**
     * Transform submitted filter data to correct format
     *
     * @param array $data
     *
     * @return array
     */
    protected function parseData($data)
    {
        $expectedChoices = [SelectRowFilterType::NOT_SELECTED_VALUE, SelectRowFilterType::SELECTED_VALUE];
        if (!isset($data['value'])
            || !in_array($data['value'], $expectedChoices)) {
            return false;
        }

        if (isset($data['in']) && !is_array($data['in'])) {
            $data['in'] = explode(',', $data['in']);
        }
        if (isset($data['out']) && !is_array($data['out'])) {
            $data['out'] = explode(',', $data['out']);
        }

        return $data;
    }
}
