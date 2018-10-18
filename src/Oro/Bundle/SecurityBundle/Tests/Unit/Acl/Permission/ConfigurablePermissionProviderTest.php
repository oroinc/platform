<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Permission;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\SecurityBundle\Acl\Permission\ConfigurablePermissionProvider;
use Oro\Bundle\SecurityBundle\Configuration\ConfigurablePermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

class ConfigurablePermissionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigurablePermissionProvider */
    protected $provider;

    /** @var ConfigurablePermissionConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $configurationProvider;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $cacheProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configurationProvider = $this->createMock(ConfigurablePermissionConfigurationProvider::class);
        $this->cacheProvider = $this->createMock(CacheProvider::class);

        $this->provider = new ConfigurablePermissionProvider($this->configurationProvider, $this->cacheProvider);
    }

    public function testBuildCache()
    {
        $data = ['some_data'];
        $this->configurationProvider->expects($this->once())->method('getConfiguration')->willReturn($data);
        $this->cacheProvider
            ->expects($this->once())
            ->method('save')
            ->with(ConfigurablePermissionProvider::CACHE_ID, $data);

        $this->provider->buildCache();
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param string $name
     * @param array $data
     * @param ConfigurablePermission $expected
     */
    public function testGet($name, array $data, ConfigurablePermission $expected)
    {
        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with(ConfigurablePermissionProvider::CACHE_ID)
            ->willReturn($data);

        $this->assertEquals($expected, $this->provider->get($name));
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        $name = 'test_name';
        $default = true;
        $entities = ['entity1', 'entity2'];
        $capabilities = ['capability1', 'capability2'];
        $data = [
            $name => [
                'default' => $default,
                'entities' => $entities,
                'capabilities' => $capabilities,
            ]
        ];

        return [
            'empty data' => [
                'name' => $name,
                'data' => [],
                'expected' => new ConfigurablePermission($name),
            ],
            'contains' => [
                'name' => $name,
                'data' => $data,
                'expected' => new ConfigurablePermission($name, $default, $entities, $capabilities),
            ],
            'not contains' => [
                'name' => 'some_other_name',
                'data' => $data,
                'expected' => new ConfigurablePermission('some_other_name'),
            ],
        ];
    }

    public function testGetWithCacheBuild()
    {
        $data = ['some_data'];

        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with(ConfigurablePermissionProvider::CACHE_ID)
            ->willReturn(false);
        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($data);
        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with(ConfigurablePermissionProvider::CACHE_ID, $data);

        $this->assertEquals(new ConfigurablePermission('test_name'), $this->provider->get('test_name'));
    }
}
