<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Command;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Console\Tester\CommandTester;

use Oro\Bundle\SecurityBundle\Command\LoadPermissionConfigurationCommand;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Entity\PermissionEntity;
use Oro\Bundle\SecurityBundle\Tests\Functional\Command\Stub\TestBundle1\TestBundle1;
use Oro\Bundle\SecurityBundle\Tests\Functional\Command\Stub\TestBundle2\TestBundle2;
use Oro\Bundle\SecurityBundle\Tests\Functional\Command\Stub\TestBundleIncorrect\TestBundleIncorrect;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Config\CumulativeResourceManager;

/**
 * @dbIsolation
 */
class LoadPermissionConfigurationCommandTest extends WebTestCase
{
    /**
     * @var PermissionConfigurationProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->initClient();

        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        $bundleIncorrect = new TestBundleIncorrect();

        $bundles = [
            $bundle1->getName() => get_class($bundle1),
            $bundle2->getName() => get_class($bundle2),
        ];

        CumulativeResourceManager::getInstance()->clear()->setBundles(
            array_merge($bundles, [$bundleIncorrect->getName() => get_class($bundleIncorrect)])
        );

        $this->provider = $this->getContainer()->get('oro_security.configuration.provider.permission_configuration');

        $this->setObjectProperty($this->provider, 'kernelBundles', $bundles);
    }

    protected function tearDown()
    {
        $this->getContainer()->get('oro_security.cache.provider.permission')->deleteAll();

        parent::tearDown();
    }

    public function testExecuteWithInvalidConfiguration()
    {
        $bundle = new TestBundleIncorrect();
        $bundles = [$bundle->getName() => get_class($bundle)];

        $this->setObjectProperty($this->provider, 'kernelBundles', $bundles);
        $result = $this->runCommand(LoadPermissionConfigurationCommand::NAME);

        $this->assertContains('Configuration of permission PERMISSION.BAD.NAME is invalid:', $result);
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array $expectedMessages
     * @param array $expectedPermissions
     */
    public function testExecute(array $expectedMessages, array $expectedPermissions)
    {
        $permissionsBefore = $this->getRepository('OroSecurityBundle:Permission')->findAll();

        $result = $this->runCommand(LoadPermissionConfigurationCommand::NAME);
        $this->assertNotEmpty($result);

        foreach ($expectedMessages as $message) {
            $this->assertContains($message, $result);
        }

        $permissions = $this->getRepository('OroSecurityBundle:Permission')->findAll();
        $this->assertCount(count($permissionsBefore) + 3, $permissions);

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
     * @param object $object
     * @param string $property
     * @param mixed $value
     */
    protected function setObjectProperty($object, $property, $value)
    {
        $reflection = ClassUtils::newReflectionObject($object);
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
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
