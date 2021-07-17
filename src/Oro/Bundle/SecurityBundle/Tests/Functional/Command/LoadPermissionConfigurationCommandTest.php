<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Command;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\SecurityBundle\Command\LoadPermissionConfigurationCommand;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfiguration;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\Config\Definition\Processor;

class LoadPermissionConfigurationCommandTest extends WebTestCase
{
    /** @var PermissionConfigurationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->initClient();

        $this->provider = $this->getContainer()
            ->get('oro_security.configuration.provider.permission_configuration');
    }

    protected function tearDown(): void
    {
        $this->provider->warmUpCache();
        $this->getContainer()->get('oro_security.cache.provider.permission')->deleteAll();

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

        static::assertStringContainsString('Configuration of permission PERMISSION.BAD.NAME is invalid:', $result);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $expectedMessages, array $expectedPermissions)
    {
        $expectedPermissions = $this->processPermissionConfig($expectedPermissions);
        $this->appendPermissionConfig($this->provider, $expectedPermissions);

        $permissionsBefore = $this->getRepository('OroSecurityBundle:Permission')->findAll();

        $result = $this->runCommand(LoadPermissionConfigurationCommand::getDefaultName());
        $this->assertNotEmpty($result);

        foreach ($expectedMessages as $message) {
            static::assertStringContainsString($message, $result);
        }

        $permissions = $this->getRepository('OroSecurityBundle:Permission')->findAll();
        $this->assertCount(count($permissionsBefore) + count($expectedPermissions), $permissions);

        foreach ($expectedPermissions as $name => $permissionData) {
            $this->assertPermissionLoaded($permissions, $permissionData, $name);
        }
    }

    /**
     * @return array
     */
    public function executeDataProvider()
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
                            'OroTestFrameworkBundle:TestActivity',
                            'OroTestFrameworkBundle:Product',
                            'OroTestFrameworkBundle:TestActivityTarget',
                        ],
                        'exclude_entities' => [
                            'OroTestFrameworkBundle:Item',
                            'OroTestFrameworkBundle:ItemValue',
                            'OroTestFrameworkBundle:WorkflowAwareEntity',
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

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className)->getRepository($className);
    }

    /**
     * @param array $config
     * @return array
     */
    private function processPermissionConfig(array $config)
    {
        $processor = new Processor();

        return $processor->processConfiguration(new PermissionConfiguration(), [$config]);
    }

    private function appendPermissionConfig(PermissionConfigurationProvider $provider, array $newPermissions)
    {
        $provider->ensureCacheWarmedUp();

        $configProp = ReflectionUtil::getProperty(new \ReflectionClass($provider), 'config');
        $configProp->setAccessible(true);
        $configProp->setValue(
            $provider,
            array_merge($configProp->getValue($provider), $newPermissions)
        );
    }

    /**
     * @param array|Permission[] $permissions
     * @param array $expected
     * @param string $name
     */
    protected function assertPermissionLoaded(array $permissions, array $expected, $name)
    {
        $found = false;
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
     * @return array
     */
    protected function getPermissionEntityNames(Collection $permissionEntities)
    {
        $entities = [];
        foreach ($permissionEntities as $permissionEntity) {
            $entities[] = $permissionEntity->getName();
        }

        return $entities;
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
