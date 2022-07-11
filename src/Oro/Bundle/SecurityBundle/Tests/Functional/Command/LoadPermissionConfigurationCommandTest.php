<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Command;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\SecurityBundle\Command\LoadPermissionConfigurationCommand;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfiguration;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Entity\ItemValue;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Config\Definition\Processor;

class LoadPermissionConfigurationCommandTest extends WebTestCase
{
    private PermissionConfigurationProvider $provider;

    protected function setUp(): void
    {
        $this->initClient();

        $this->provider = $this->getContainer()
            ->get('oro_security.configuration.provider.permission_configuration');
    }

    protected function tearDown(): void
    {
        $this->provider->warmUpCache();
        $this->getContainer()->get('oro_security.cache.provider.permission')->clear();

        parent::tearDown();
    }

    public function testExecuteWithInvalidConfiguration()
    {
        $newPermissions = $this->processPermissionConfig([
            'PERMISSION.BAD.NAME' => [
                'label' => 'Label for Permission with a Bad Name'
            ]
        ]);

        $this->appendPermissionConfig($this->provider, $newPermissions);

        $result = $this->runCommand(LoadPermissionConfigurationCommand::getDefaultName());

        self::assertStringContainsString('Configuration of permission PERMISSION.BAD.NAME is invalid:', $result);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $expectedMessages, array $expectedPermissions)
    {
        $expectedPermissions = $this->processPermissionConfig($expectedPermissions);
        $this->appendPermissionConfig($this->provider, $expectedPermissions);

        $permissionsBefore = $this->getRepository(Permission::class)->findAll();

        $result = $this->runCommand(LoadPermissionConfigurationCommand::getDefaultName());
        $this->assertNotEmpty($result);

        foreach ($expectedMessages as $message) {
            self::assertStringContainsString($message, $result);
        }

        $permissions = $this->getRepository(Permission::class)->findAll();
        $this->assertCount(count($permissionsBefore) + count($expectedPermissions), $permissions);

        foreach ($expectedPermissions as $name => $permissionData) {
            $this->assertPermissionLoaded($permissions, $permissionData, $name);
        }
    }

    public function executeDataProvider(): array
    {
        return [
            [
                'expectedMessages' => [
                    'Loading permissions...',
                    'NotManageableEntity - is not a manageable entity class',
                ],
                'expectedPermissions' => [
                    'PERMISSION1' => [
                        'label' => 'Label for Permission 1',
                        'group_names' => ['frontend'],
                    ],
                    'PERMISSION2' => [
                        'label' => 'Label for Permission 2',
                        'group_names' => ['default', 'frontend', 'new_group'],
                        'apply_to_all' => false,
                        'apply_to_entities' => [
                            TestActivity::class,
                            Product::class,
                            TestActivityTarget::class,
                        ],
                        'exclude_entities' => [
                            Item::class,
                            ItemValue::class,
                            WorkflowAwareEntity::class,
                        ],
                        'description' => 'Permission 2 description',
                    ],
                    'PERMISSION3' => [
                        'label' => 'Label for Permission 3',
                        'group_names' => ['default'],
                        'apply_to_entities' => ['NotManageableEntity']
                    ],
                ],
            ]
        ];
    }

    private function getRepository(string $className): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository($className);
    }

    private function processPermissionConfig(array $config): array
    {
        return (new Processor())->processConfiguration(new PermissionConfiguration(), [$config]);
    }

    private function appendPermissionConfig(PermissionConfigurationProvider $provider, array $newPermissions)
    {
        $provider->ensureCacheWarmedUp();

        ReflectionUtil::setPropertyValue(
            $provider,
            'config',
            array_merge(ReflectionUtil::getPropertyValue($provider, 'config'), $newPermissions)
        );
    }

    private function assertPermissionLoaded(array $permissions, array $expected, string $name): void
    {
        $found = false;
        /** @var Permission $permission */
        foreach ($permissions as $permission) {
            if ($permission->getName() === $name) {
                $this->assertSame($expected['label'], $permission->getLabel());
                $this->assertSame(
                    $this->getConfigurationOption($expected, 'apply_to_all', true),
                    $permission->isApplyToAll()
                );
                $this->assertSame(
                    $this->getConfigurationOption($expected, 'group_names', true),
                    $permission->getGroupNames()
                );
                $this->assertEquals(
                    $this->getConfigurationOption($expected, 'exclude_entities', []),
                    $this->getPermissionEntityNames($permission->getExcludeEntities())
                );
                $this->assertEquals(
                    $this->getConfigurationOption($expected, 'apply_to_entities', []),
                    $this->getPermissionEntityNames($permission->getApplyToEntities())
                );
                $this->assertSame(
                    $this->getConfigurationOption($expected, 'description', ''),
                    $permission->getDescription()
                );
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    /**
     * @param Collection|PermissionEntity[] $permissionEntities
     *
     * @return string[]
     */
    private function getPermissionEntityNames(Collection $permissionEntities): array
    {
        $entities = [];
        foreach ($permissionEntities as $permissionEntity) {
            $entities[] = $permissionEntity->getName();
        }

        return $entities;
    }

    private function getConfigurationOption(array $options, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $options)) {
            return $options[$key];
        }

        return $default;
    }
}
