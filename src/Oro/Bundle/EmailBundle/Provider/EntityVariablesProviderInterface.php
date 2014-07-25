<?php

namespace Oro\Bundle\EmailBundle\Provider;

interface EntityVariablesProviderInterface
{
    /**
     * Gets definitions of variables available in a template
     *
     * @param string $entityClass The entity class name
     *
     * @return array The list of variables in the following format:
     *                  {variable name} => array
     *                      'type' => {variable data type}
     *                      'name' => {translated variable name}
     */
    public function getVariableDefinitions($entityClass);

    /**
     * Gets getters of variables available in a template
     *
     * @param string $entityClass The entity class name
     *
     * @return string[] The list of getter names
     *                      key = {variable name}
     *                      value = {method name} // can be NULL if entity field is public
     */
    public function getVariableGetters($entityClass);
}
