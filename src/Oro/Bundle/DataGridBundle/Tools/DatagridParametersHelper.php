<?php

namespace Oro\Bundle\DataGridBundle\Tools;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

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
}
