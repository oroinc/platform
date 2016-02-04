<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\SecurityBundle\Configuration\PermissionConfiguration;
use Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Configuration\PermissionListConfiguration;
use Oro\Bundle\SecurityBundle\Tests\Unit\Configuration\Stub\TestBundle;
use Oro\Component\Config\CumulativeResourceManager;

class PermissionConfigurationProviderTest extends \PHPUnit_Framework_TestCase
{
    const PERMISSION1 = [
        'label' => 'Label for Permission 1',
        'group_names' => ['frontend'],
        'apply_to_all' => true,
        'apply_to_entities' => [],
        'exclude_entities' => [],
    ];

    const PERMISSION2 = [
        'label' => 'Label for Permission 2',
        'group_names' => ['', 'frontend'],
        'apply_to_all' => false,
        'apply_to_entities' => ['Entity1', 'Entity2'],
        'exclude_entities' => ['Entity3', 'Entity4'],
        'description' => 'Permission 2 description',
    ];

    const PERMISSION3 = [
        'label' => 'Label for Permission 3',
        'group_names' => ['default'],
        'apply_to_all' => true,
        'apply_to_entities' => [],
        'exclude_entities' => [],
    ];

    /**
     * @var PermissionConfigurationProvider
     */
    protected $provider;

    protected function setUp()
    {
        $bundle  = new TestBundle();
        $bundles = [$bundle->getName() => get_class($bundle)];
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles($bundles);
        $this->provider = new PermissionConfigurationProvider(
            new PermissionListConfiguration(new PermissionConfiguration()),
            $bundles
        );
    }

    protected function tearDown()
    {
        unset($this->provider);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testIncorrectConfiguration()
    {
        $this->loadConfig('permissionsIncorrect.yml');
        $this->provider->getPermissionConfiguration();
    }

    public function testCorrectConfiguration()
    {
        $expectedPermissions = [
            'PERMISSION1' => self::PERMISSION1,
            'PERMISSION2' => self::PERMISSION2,
            'PERMISSION3' => self::PERMISSION3,
        ];

        $this->loadConfig('permissionsCorrect.yml');
        $permissions = $this->provider->getPermissionConfiguration();
        $this->assertArrayHasKey(PermissionConfigurationProvider::ROOT_NODE_NAME, $permissions);
        $this->assertEquals($expectedPermissions, $permissions[PermissionConfigurationProvider::ROOT_NODE_NAME]);
    }

    public function testFilterPermissionsConfiguration()
    {
        $expectedPermissions = [
            'PERMISSION1' => self::PERMISSION1,
            'PERMISSION3' => self::PERMISSION3,
        ];

        $this->loadConfig('permissionsCorrect.yml');
        $permissions = $this->provider->getPermissionConfiguration(array_keys($expectedPermissions));
        $this->assertArrayHasKey(PermissionConfigurationProvider::ROOT_NODE_NAME, $permissions);
        $this->assertEquals($expectedPermissions, $permissions[PermissionConfigurationProvider::ROOT_NODE_NAME]);
    }

    protected function loadConfig($path)
    {
        $reflection = new \ReflectionClass('Oro\Bundle\SecurityBundle\Configuration\PermissionConfigurationProvider');
        $reflectionProperty = $reflection->getProperty('configPath');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->provider, $path);
    }
}
