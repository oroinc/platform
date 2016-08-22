<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Configuration;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

class ConfigurationManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigurationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurationProvider;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    protected function setUp()
    {
        $this->configurationProvider = $this->getMockBuilder(ConfigurationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configurationManager = new ConfigurationManager($this->configurationProvider);
    }

    public function testGetDefault()
    {
        $feature = 'feature';
        $node = 'node';
        $default = 'default';

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([]);

        $this->assertEquals($default, $this->configurationManager->get($feature, $node, $default));
    }

    public function testGet()
    {
        $feature = 'feature';
        $node = 'node';
        $default = 'default';
        $value = 'value';

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->willReturn(['feature' => ['node' => $value]]);

        $this->assertEquals($value, $this->configurationManager->get($feature, $node, $default));
    }
}
