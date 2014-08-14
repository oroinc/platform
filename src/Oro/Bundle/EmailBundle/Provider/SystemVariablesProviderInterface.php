<?php

namespace Oro\Bundle\EmailBundle\Provider;

interface SystemVariablesProviderInterface
{
    /**
     * Gets definitions of variables available in a template
     *
     * @return array The list of variables in the following format:
     *                  {variable name} => array
     *                      'type'  => {variable data type}
     *                      'label' => {translated variable name}
     */
    public function getVariableDefinitions();

    /**
     * Gets values of variables available in a template
     *
     * @return array The list of values
     *                  key   = {variable name}
     *                  value = {variable value}
     */
    public function getVariableValues();
}
