<?php

namespace Oro\Bundle\SecurityBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\SecurityBundle\Acl\Permission\ConfigurablePermissionProvider;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

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
        $cache = $this->getContainer()->get('oro_security.cache.provider.configurable_permission');
        $data = $cache->fetch(ConfigurablePermissionProvider::CACHE_ID);

        if ($refreshCache || $data === false) {
            $this->loadPermissions();

            $data = $cache->fetch(ConfigurablePermissionProvider::CACHE_ID);
        }

        $data = array_merge_recursive($data, [$group => ['default' => true, $type => $config]]);

        $cache->save(ConfigurablePermissionProvider::CACHE_ID, $data);
    }

    protected function loadPermissions()
    {
        $provider = $this->getContainer()->get('oro_security.acl.configurable_permission_provider');
        $provider->buildCache();
    }
}
