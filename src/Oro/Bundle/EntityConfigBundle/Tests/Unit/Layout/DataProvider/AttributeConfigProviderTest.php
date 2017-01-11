<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\EntityConfigBundle\Layout\DataProvider\AttributeConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

class AttributeConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfig()
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $config = $this->createMock(ConfigInterface::class);
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with('entityClass', 'fooFieldName')
            ->willReturn($config);

        $provider = new AttributeConfigProvider($configProvider);

        $this->assertEquals($config, $provider->getConfig('entityClass', 'fooFieldName'));
    }
}
