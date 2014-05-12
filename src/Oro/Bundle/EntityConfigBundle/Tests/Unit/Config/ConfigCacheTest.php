<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigCache;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class ConfigCacheTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $cacheProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $modelCacheProvider;

    public function setUp()
    {
        $this->cacheProvider = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->disableOriginalConstructor()
            ->setMethods(array('fetch', 'save', 'delete', 'deleteAll'))
            ->getMockForAbstractClass();

        $this->modelCacheProvider = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->disableOriginalConstructor()
            ->setMethods(array('fetch', 'save', 'delete', 'deleteAll'))
            ->getMockForAbstractClass();
    }

    protected function tearDown()
    {
        unset($this->cacheProvider);
        unset($this->modelCacheProvider);
    }

    public function testPutConfigInCache()
    {
        $className   = 'testClass';
        $scope       = 'testScope';
        $configId    = new EntityConfigId($scope, $className);
        $config      = new Config($configId);
        $configCache = new ConfigCache($this->cacheProvider, $this->modelCacheProvider);

        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));
        $this->assertTrue($configCache->putConfigInCache($config));
    }

    public function testRemoveConfigFromCache()
    {
        $className   = 'testClass';
        $scope       = 'testScope';
        $configId    = new EntityConfigId($scope, $className);
        $configCache = new ConfigCache($this->cacheProvider, $this->modelCacheProvider);

        $this->cacheProvider->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));
        $this->assertTrue($configCache->removeConfigFromCache($configId));
    }

    public function testRemoveAll()
    {
        $configCache = new ConfigCache($this->cacheProvider, $this->modelCacheProvider);

        $this->cacheProvider->expects($this->once())
            ->method('deleteAll')
            ->will($this->returnValue(true));
        $this->assertTrue($configCache->removeAll());
    }

    public function testLoadConfigFromCache()
    {
        $className   = 'testClass';
        $scope       = 'testScope';
        $configId    = new EntityConfigId($scope, $className);
        $config      = new Config($configId);
        $configCache = new ConfigCache($this->cacheProvider, $this->modelCacheProvider);

        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue($config));
        $this->assertEquals($config, $configCache->loadConfigFromCache($configId));
    }

    /**
     * @dataProvider setConfigurableProvider
     */
    public function testSetConfigurable($flag, $expectedCacheValue)
    {
        $className   = 'testClass';
        $configCache = new ConfigCache($this->cacheProvider, $this->modelCacheProvider);

        $this->modelCacheProvider->expects($this->once())
            ->method('save')
            ->with($className, $this->identicalTo($expectedCacheValue))
            ->will($this->returnValue(true));
        $this->assertTrue($configCache->setConfigurable($flag, $className));
    }

    public function setConfigurableProvider()
    {
        return [
            [true, true],
            [false, null],
        ];
    }

    /**
     * @dataProvider getConfigurableProvider
     */
    public function testGetConfigurable($expectedFlag, $cachedValue)
    {
        $className   = 'testClass';
        $configCache = new ConfigCache($this->cacheProvider, $this->modelCacheProvider);

        $this->modelCacheProvider->expects($this->once())
            ->method('fetch')
            ->with($className)
            ->will($this->returnValue($cachedValue));
        $this->assertTrue($expectedFlag === $configCache->getConfigurable($className));
    }

    public function getConfigurableProvider()
    {
        return [
            [true, true],
            [null, false],
            [false, null],
        ];
    }

    public function testRemoveAllConfigurable()
    {
        $configCache = new ConfigCache($this->cacheProvider, $this->modelCacheProvider);

        $this->modelCacheProvider->expects($this->once())
            ->method('deleteAll')
            ->will($this->returnValue(true));
        $this->assertTrue($configCache->removeAllConfigurable());
    }
}
