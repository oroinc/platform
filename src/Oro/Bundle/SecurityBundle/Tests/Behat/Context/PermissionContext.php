<?php

namespace Oro\Bundle\SecurityBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\SecurityBundle\Configuration\ConfigurablePermissionConfigurationProvider;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Provider\PhpArrayConfigCacheModifier;

/**
 * Provides a set of steps to test security permissions.
 */
class PermissionContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    //@codingStandardsIgnoreStart
    /**
     * @Given /^permission (?P<permission>[\w\_]*) for entity (?P<entity>[\w\\]*) and group (?P<group>[\w\_]*) restricts in application$/
     *
     * @param string $permission
     * @param string $entity
     * @param string $group
     */
    //@codingStandardsIgnoreEnd
    public function restrictPermissionForEntity($permission, $entity, $group)
    {
        $this->addPermissionConfig([$entity => [strtoupper($permission) => false]], $group, 'entities');
    }

    /**
     * @Given /^all permissions for entity (?P<entity>[\w\\]*) and group (?P<group>[\w\_]*) restricts in application$/
     *
     * @param string $entity
     * @param string $group
     */
    public function restrictAllPermissionsForEntity($entity, $group)
    {
        $this->addPermissionConfig([$entity => false], $group, 'entities', true);
    }

    /**
     * @Given /^capability (?P<capability>[\w\_]*) and group (?P<group>[\w\_]*) restricts in application$/
     *
     * @param string $capability
     * @param string $group
     */
    public function restrictCapability($capability, $group)
    {
        $this->addPermissionConfig([$capability => false], $group, 'capabilities');
    }

    /**
     * @param array $config
     * @param string $group
     * @param string $type
     * @param bool $refreshCache
     */
    protected function addPermissionConfig(array $config, $group, $type, $refreshCache = false)
    {
        /** @var ConfigurablePermissionConfigurationProvider $configurationProvider */
        $configurationProvider = $this->getContainer()
            ->get('oro_security.configuration.provider.configurable_permission_configuration');
        $configurationModifier = new PhpArrayConfigCacheModifier($configurationProvider);

        if ($refreshCache) {
            $configurationModifier->resetCache();
        }

        $data = $configurationProvider->getConfiguration();
        $data = array_merge_recursive($data, [$group => ['default' => true, $type => $config]]);
        $configurationModifier->updateCache($data);
    }
}
