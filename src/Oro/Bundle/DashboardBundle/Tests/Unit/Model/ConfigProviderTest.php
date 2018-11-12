<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Event\WidgetConfigurationLoadEvent;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;

class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    private $eventDispatcher;

    public function setUp()
    {
        $this->eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    }

    public function testHasConfig()
    {
        $existConfigKey = 'exist key';
        $configProvider = new ConfigProvider(array($existConfigKey => array()), $this->eventDispatcher);

        $this->assertFalse($configProvider->hasConfig('not found config'));
        $this->assertTrue($configProvider->hasConfig($existConfigKey));
    }

    public function testGetConfig()
    {
        $existConfigKey = 'exist key';
        $expected = array('label' => 'test label');
        $configProvider = new ConfigProvider(array($existConfigKey => $expected), $this->eventDispatcher);

        $this->assertEquals($expected, $configProvider->getConfig($existConfigKey));
    }

    /**
     * @expectedException \Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Can't find configuration for: not found config
     */
    public function testGetConfigHasNoKeyException()
    {
        $configProvider = new ConfigProvider(array(), $this->eventDispatcher);
        $configProvider->getConfig('not found config');
    }

    public function testGetConfigs()
    {
        $expected = array('exist key' => array('label' => 'test label'));
        $configProvider = new ConfigProvider($expected, $this->eventDispatcher);

        $this->assertEquals($expected, $configProvider->getConfigs());
    }

    public function testGetDashboardConfigs()
    {
        $expected = array('label' => 'test label');
        $configProvider = new ConfigProvider(array(
            ConfigProvider::NODE_DASHBOARD => $expected
        ), $this->eventDispatcher);

        $this->assertEquals($expected, $configProvider->getDashboardConfigs());
    }

    public function testGetWidgetConfigs()
    {
        $expected = array('label' => 'test label');
        $configProvider = new ConfigProvider(array(ConfigProvider::NODE_WIDGET => $expected), $this->eventDispatcher);

        $this->assertEquals($expected, $configProvider->getWidgetConfigs());
    }

    public function testGetDashboardsConfig()
    {
        $dashboardName = 'test dashboard';
        $expected = array('label' => 'test label');
        $config = array(ConfigProvider::NODE_DASHBOARD => array($dashboardName => $expected));
        $configProvider = new ConfigProvider($config, $this->eventDispatcher);

        $this->assertEquals($expected, $configProvider->getDashboardConfig($dashboardName));
    }

    public function testHasDashboardsConfig()
    {
        $dashboardName = 'test dashboard';
        $config = array(ConfigProvider::NODE_DASHBOARD => array($dashboardName => array('label' => 'test label')));
        $configProvider = new ConfigProvider($config, $this->eventDispatcher);

        $this->assertTrue($configProvider->hasDashboardConfig($dashboardName));
        $this->assertFalse($configProvider->hasDashboardConfig('incorrect dashboard'));
    }

    public function testGetWidgetConfig()
    {
        $widgetName = 'test dashboard';
        $expected = array('label' => 'test label');
        $config = array(ConfigProvider::NODE_WIDGET => array($widgetName => $expected));
        $configProvider = new ConfigProvider($config, $this->eventDispatcher);

        $this->assertEquals($expected, $configProvider->getWidgetConfig($widgetName));
    }

    public function testHasWidgetConfig()
    {
        $widgetName = 'test dashboard';
        $config = array(ConfigProvider::NODE_WIDGET => array($widgetName => array('label' => 'test label')));
        $configProvider = new ConfigProvider($config, $this->eventDispatcher);

        $this->assertTrue($configProvider->hasWidgetConfig($widgetName));
        $this->assertFalse($configProvider->hasWidgetConfig('incorrect widget'));
    }

    public function testGetWidgetConfigShouldReturnConfigurationOfWidgetFromEvent()
    {
        $this->eventDispatcher
            ->expects($this->once())
            ->method('hasListeners')
            ->with(WidgetConfigurationLoadEvent::EVENT_NAME)
            ->will($this->returnValue(true));

        $eventConfiguration = ['k12' => 'opt'];
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->will(
                $this->returnCallback(function ($name, WidgetConfigurationLoadEvent $event) use ($eventConfiguration) {
                    $event->setConfiguration($eventConfiguration);

                    return $event;
                })
            );

        $config = [ConfigProvider::NODE_WIDGET => ['widget' => []]];
        $configProvider = new ConfigProvider($config, $this->eventDispatcher);

        $this->assertTrue($configProvider->hasWidgetConfig('widget'));
        $this->assertEquals($eventConfiguration, $configProvider->getWidgetConfig('widget'));
    }

    /**
     * @expectedException \Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException
     */
    public function testGetWidgetConfigHasNoKeyException()
    {
        $configProvider = new ConfigProvider(array(), $this->eventDispatcher);
        $configProvider->getWidgetConfig('not found config');
    }

    /**
     * @expectedException \Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException
     */
    public function testGetDashboardConfigHasNoKeyException()
    {
        $configProvider = new ConfigProvider(array(), $this->eventDispatcher);
        $configProvider->getDashboardConfig('not found config');
    }
}
