<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Acl;

use Oro\Bundle\TestFrameworkBundle\Provider\PhpArrayConfigCacheModifier;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Role;

abstract class AbstractPermissionConfigurableTestCase extends WebTestCase
{
    private static function getConfigurationModifier(): PhpArrayConfigCacheModifier
    {
        return new PhpArrayConfigCacheModifier(
            self::getClientInstance()->getContainer()
                ->get('oro_security.configuration.provider.configurable_permission_configuration')
        );
    }

    /**
     * @beforeResetClient
     */
    public static function buildOriginCache()
    {
        self::getConfigurationModifier()->resetCache();
    }

    /**
     * @dataProvider configurablePermissionCapabilitiesProvider
     *
     * @param array $config
     * @param string $action
     * @param bool $expected
     */
    public function testConfigurableCapabilities(array $config, $action, $expected)
    {
        self::getConfigurationModifier()->updateCache($config);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl($this->getRouteName(), ['id' => $this->getRole()->getId()])
        );

        if ($expected) {
            static::assertStringContainsString($action, $crawler->html());
        } else {
            static::assertStringNotContainsString($action, $crawler->html());
        }
    }

    /**
     * @dataProvider configurablePermissionEntitiesProvider
     */
    public function testConfigurableEntities(array $config, \Closure $assertGridData)
    {
        self::getConfigurationModifier()->updateCache($config);

        /** @var Role $role */
        $role = $this->getRole();
        $gridData = $this->requestGrid($this->getGridName(), ['role' => $role])['data'];
        $assertGridData($gridData);
    }

    /**
     * @param array $gridData
     * @param string $entityClass
     * @param string $permissionName
     */
    protected function assertHasEntityPermission(array $gridData, $entityClass, $permissionName)
    {
        try {
            $permissions = array_filter(
                $gridData,
                function ($item) use ($entityClass) {
                    return sprintf('entity:%s', $entityClass) === $item['identity'];
                }
            );

            $this->assertNotEmpty($permissions);
            $this->assertArrayHasKey('permissions', reset($permissions));

            $permissions = reset($permissions)['permissions'];
            $permissions = array_filter(
                $permissions,
                function ($item) use ($permissionName) {
                    return $permissionName === $item['name'];
                }
            );

            $this->assertNotEmpty($permissions);
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                sprintf(
                    'Failed asserting that enable %s configurable permission for entity %s',
                    $permissionName,
                    $entityClass
                )
            );
        }
    }

    /**
     * @param array $gridData
     * @param string $entityClass
     * @param string $permissionName
     */
    protected function assertNotHasEntityPermission(array $gridData, $entityClass, $permissionName)
    {
        try {
            $this->assertHasEntityPermission($gridData, $entityClass, $permissionName);
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            return;
        }

        throw new \PHPUnit\Framework\AssertionFailedError(
            sprintf(
                'Failed asserting that disable %s configurable permission for entity %s',
                $entityClass,
                $permissionName
            )
        );
    }

    /**
     * @param string $gridName
     * @param array $parameters
     *
     * @return array
     */
    protected function requestGrid($gridName, array $parameters = [])
    {
        $manager = $this->getContainer()->get('oro_datagrid.datagrid.manager');
        return $manager->getDatagrid($gridName, $parameters)->getData()->toArray();
    }

    /**
     * @return array|\Generator
     */
    abstract public function configurablePermissionEntitiesProvider();

    /**
     * @return array|\Generator
     */
    abstract public function configurablePermissionCapabilitiesProvider();

    /**
     * @return Role
     */
    abstract protected function getRole();

    /**
     * @return string
     */
    abstract protected function getGridName();

    /**
     * @return string
     */
    abstract protected function getRouteName();
}
