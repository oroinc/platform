<?php

namespace Oro\Bundle\SecurityBundle\Configuration;

use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Exception\MissedRequiredOptionException;

class PermissionConfigurationBuilder
{
    /**
     * @param array $configuration
     * @return Permission[]
     */
    public function buildPermissions(array $configuration)
    {
        $permissions = [];
        foreach ($configuration as $name => $permissionConfiguration) {
            $permissions[] = $this->buildPermission($name, $permissionConfiguration);
        }

        return $permissions;
    }

    /**
     * @param $name
     * @param array $configuration
     * @return Permission
     */
    public function buildPermission($name, array $configuration)
    {
        $this->assertConfigurationOptions($configuration, ['label']);

        $permission = new Permission();
        $permission
            ->setName($name)
            ->setLabel($configuration['label'])
            ->setApplyToAll(array_key_exists('apply_to_all', $configuration) ? $configuration['apply_to_all'] : true)
            ->setGroupNames(array_key_exists('group_names', $configuration) ? $configuration['group_names'] : [])
            ->setExcludeEntities(
                array_key_exists('exclude_entities', $configuration) ? $configuration['exclude_entities'] : []
            )
            ->setApplyToEntities(
                array_key_exists('apply_to_entities', $configuration) ? $configuration['apply_to_entities'] : []
            )
            ->setDescription(array_key_exists('description', $configuration) ? $configuration['description'] : '');

        return $permission;
    }

    /**
     * @param array $configuration
     * @param array $requiredOptions
     * @throws MissedRequiredOptionException
     */
    protected function assertConfigurationOptions(array $configuration, array $requiredOptions)
    {
        foreach ($requiredOptions as $optionName) {
            if (!isset($configuration[$optionName])) {
                throw new MissedRequiredOptionException(sprintf('Configuration option "%s" is required', $optionName));
            }
        }
    }

    /**
     * @param array $options
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfigurationOption(array $options, $key, $default = null)
    {
        if (array_key_exists($key, $options)) {
            return $options[$key];
        }

        return $default;
    }
}
