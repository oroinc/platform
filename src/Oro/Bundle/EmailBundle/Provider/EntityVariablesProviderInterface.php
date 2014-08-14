<?php

namespace Oro\Bundle\EmailBundle\Provider;

interface EntityVariablesProviderInterface
{
    /**
     * Gets definitions of variables available in a template
     *
     * @param string $entityClass The entity class name. If it is not specified the definitions for all
     *                            entities are returned.
     *
     * @return array The list of variables in the following format:
     *                  {variable name} => array
     *                      'type'  => {variable data type}
     *                      'label' => {translated variable name}
     *               If a field represents a relation the following attributes are added:
     *                      'related_entity_name' => {related entity full class name}
     *               If $entityClass is NULL variables are grouped by entity class:
     *                  {entity class} => array
     *                      {variable name} => array of attributes described above
     */
    public function getVariableDefinitions($entityClass = null);

    /**
     * Gets getters of variables available in a template
     *
     * @param string $entityClass The entity class name If it is not specified the getters for all
     *                            entities are returned.
     *
     * @return string[] The list of getter names
     *                      key = {variable name}
     *                      value = {method name} // can be NULL if entity field is public
     */
    public function getVariableGetters($entityClass = null);
}
