<?php

namespace Oro\Bundle\DataGridBundle\Tools;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;

class DatagridParametersHelper
{
    /**
     * @param ParameterBag $datagridParameters
     * @param string $parameterName
     *
     * @return mixed|null
     */
    public function getFromParameters(ParameterBag $datagridParameters, string $parameterName)
    {
        if ($datagridParameters->has($parameterName)) {
            $parameter = $datagridParameters->get($parameterName);
        }

        return $parameter ?? null;
    }

    /**
     * @param ParameterBag $datagridParameters
     * @param string $parameterName
     *
     * @return mixed|null
     */
    public function getFromMinifiedParameters(ParameterBag $datagridParameters, string $parameterName)
    {
        // Try to fetch from minified parameters if any.
        if ($datagridParameters->has(ParameterBag::MINIFIED_PARAMETERS)) {
            $minifiedParameters = $datagridParameters->get(ParameterBag::MINIFIED_PARAMETERS);
            if (array_key_exists($parameterName, $minifiedParameters)) {
                $parameter = $minifiedParameters[$parameterName];
            }
        }

        return $parameter ?? null;
    }

    /**
     * @param ParameterBag $dataGridParameters
     * @param string $filterName
     */
    public function resetFilter(ParameterBag $dataGridParameters, string $filterName): void
    {
        $filters = $dataGridParameters->get(AbstractFilterExtension::FILTER_ROOT_PARAM);
        if ($filters) {
            unset($filters[$filterName]);
            $dataGridParameters->set(AbstractFilterExtension::FILTER_ROOT_PARAM, $filters);
        }

        $minifiedFilters = $dataGridParameters->get(ParameterBag::MINIFIED_PARAMETERS);
        if ($minifiedFilters) {
            unset($minifiedFilters[AbstractFilterExtension::MINIFIED_FILTER_PARAM][$filterName]);
            $dataGridParameters->set(ParameterBag::MINIFIED_PARAMETERS, $minifiedFilters);
        }
    }
}
