<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Acl;

use Oro\Bundle\TestFrameworkBundle\Provider\PhpArrayConfigCacheModifier;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\AbstractRole;

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
     */
    public function testConfigurableCapabilities(array $config, string $action, bool $expected)
    {
        self::getConfigurationModifier()->updateCache($config);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl($this->getRouteName(), ['id' => $this->getRole()->getId()])
        );

        if ($expected) {
            self::assertStringContainsString($action, $crawler->html());
        } else {
            self::assertStringNotContainsString($action, $crawler->html());
        }
    }

    /**
     * @dataProvider configurablePermissionEntitiesProvider
     */
    public function testConfigurableEntities(array $config, \Closure $assertGridData)
    {
        self::getConfigurationModifier()->updateCache($config);

        $role = $this->getRole();
        $gridData = $this->requestGrid($this->getGridName(), ['role' => $role])['data'];
        $assertGridData($gridData);
    }

    protected function assertHasEntityPermission(array $gridData, string $entityClass, string $permissionName): void
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

    protected function assertNotHasEntityPermission(array $gridData, string $entityClass, string $permissionName): void
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

    protected function requestGrid(string $gridName, array $parameters = []): array
    {
        $manager = $this->getContainer()->get('oro_datagrid.datagrid.manager');

        return $manager->getDatagrid($gridName, $parameters)->getData()->toArray();
    }

    abstract public function configurablePermissionEntitiesProvider(): array;

    abstract public function configurablePermissionCapabilitiesProvider(): array;

    abstract protected function getRole(): AbstractRole;

    abstract protected function getGridName(): string;

    abstract protected function getRouteName(): string;
}
