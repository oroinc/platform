<?php

namespace Oro\Bundle\EmailBundle\Provider;

class VariablesProvider
{
    /** @var SystemVariablesProviderInterface[] */
    protected $systemVariablesProviders = [];

    /** @var EntityVariablesProviderInterface[] */
    protected $entityVariablesProviders = [];

    /**
     * @param SystemVariablesProviderInterface $provider
     */
    public function addSystemVariablesProvider(SystemVariablesProviderInterface $provider)
    {
        $this->systemVariablesProviders[] = $provider;
    }

    /**
     * @param EntityVariablesProviderInterface $provider
     */
    public function addEntityVariablesProvider(EntityVariablesProviderInterface $provider)
    {
        $this->entityVariablesProviders[] = $provider;
    }

    /**
     * Gets system variables available in a template
     *
     * @return array The list of variables in the following format:
     *                  {variable name} => array
     *                      'type' => {variable data type}
     *                      'name' => {translated variable name}
     */
    public function getSystemVariableDefinitions()
    {
        $result = [];

        foreach ($this->systemVariablesProviders as $provider) {
            $result = array_merge(
                $result,
                $provider->getVariableDefinitions()
            );
        }

        return $result;
    }

    /**
     * Gets entity related variables available in a template
     *
     * @param string $entityClass The entity class name. If it is not specified the definitions for all
     *                            entities are returned.
     *
     * @return array The list of variables in the following format:
     *                  {variable name} => array
     *                      'type' => {variable data type}
     *                      'name' => {translated variable name}
     */
    public function getEntityVariableDefinitions($entityClass = null)
    {
        $result = [];

        foreach ($this->entityVariablesProviders as $provider) {
            $result = array_merge_recursive(
                $result,
                $provider->getVariableDefinitions($entityClass)
            );
        }

        return $result;
    }

    /**
     * Gets values of system variables available in a template
     *
     * @return array The list of values
     *                  key   = {variable name}
     *                  value = {variable value}
     */
    public function getSystemVariableValues()
    {
        $result = [];

        foreach ($this->systemVariablesProviders as $provider) {
            $result = array_merge(
                $result,
                $provider->getVariableValues()
            );
        }

        return $result;
    }

    /**
     * Gets getters of entity related variables available in a template
     *
     * @param string $entityClass The entity class name. If it is not specified the definitions for all
     *                            entities are returned.
     *
     * @return string[] The list of getter names
     *                      key = {variable name}
     *                      value = {method name} // can be NULL if entity field is public
     */
    public function getEntityVariableGetters($entityClass = null)
    {
        $result = [];

        foreach ($this->entityVariablesProviders as $provider) {
            $result = array_merge_recursive(
                $result,
                $provider->getVariableGetters($entityClass)
            );
        }

        return $result;
    }
}
