<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider;

use Oro\Bundle\DashboardBundle\Provider\ConfigProvider;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testHasConfig()
    {
        $existConfigKey = 'exist key';
        $configProvider = new ConfigProvider(array($existConfigKey => array()));

        $this->assertFalse($configProvider->hasConfig('not found config'));
        $this->assertTrue($configProvider->hasConfig($existConfigKey));
    }

    public function testGetConfig()
    {
        $existConfigKey = 'exist key';
        $expected = array('label' => 'test label');
        $configProvider = new ConfigProvider(array($existConfigKey => $expected));

        $this->assertEquals($expected, $configProvider->getConfig($existConfigKey));
    }

    /**
     * @expectedException \Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Can't find configuration for: not found config
     */
    public function testGetConfigHasNoKeyException()
    {
        $configProvider = new ConfigProvider(array());
        $configProvider->getConfig('not found config');
    }

    public function testGetConfigs()
    {
        $expected = array('exist key' => array('label' => 'test label'));
        $configProvider = new ConfigProvider($expected);

        $this->assertEquals($expected, $configProvider->getConfigs());
    }

    public function testGetDashboardConfigs()
    {
        $expected = array('label' => 'test label');
        $configProvider = new ConfigProvider(array(ConfigProvider::NODE_DASHBOARD => $expected));

        $this->assertEquals($expected, $configProvider->getDashboardConfigs());
    }

    public function testGetWidgetConfigs()
    {
        $expected = array('label' => 'test label');
        $configProvider = new ConfigProvider(array(ConfigProvider::NODE_WIDGET => $expected));

        $this->assertEquals($expected, $configProvider->getWidgetConfigs());
    }

    public function testGetDashboardsConfig()
    {
        $dashboardName = 'test dashboard';
        $expected = array('label' => 'test label');
        $config = array(ConfigProvider::NODE_DASHBOARD => array($dashboardName => $expected));
        $configProvider = new ConfigProvider($config);

        $this->assertEquals($expected, $configProvider->getDashboardConfig($dashboardName));
    }

    public function testHasDashboardsConfig()
    {
        $dashboardName = 'test dashboard';
        $config = array(ConfigProvider::NODE_DASHBOARD => array($dashboardName => array('label' => 'test label')));
        $configProvider = new ConfigProvider($config);

        $this->assertTrue($configProvider->hasDashboardConfig($dashboardName));
        $this->assertFalse($configProvider->hasDashboardConfig('incorrect dashboard'));
    }

    public function testGetWidgetConfig()
    {
        $widgetName = 'test dashboard';
        $expected = array('label' => 'test label');
        $config = array(ConfigProvider::NODE_WIDGET => array($widgetName => $expected));
        $configProvider = new ConfigProvider($config);

        $this->assertEquals($expected, $configProvider->getWidgetConfig($widgetName));
    }

    public function testHasWidgetConfig()
    {
        $widgetName = 'test dashboard';
        $config = array(ConfigProvider::NODE_WIDGET => array($widgetName => array('label' => 'test label')));
        $configProvider = new ConfigProvider($config);

        $this->assertTrue($configProvider->hasWidgetConfig($widgetName));
        $this->assertFalse($configProvider->hasWidgetConfig('incorrect widget'));
    }

    /**
     * @expectedException \Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException
     */
    public function testGetWidgetConfigHasNoKeyException()
    {
        $configProvider = new ConfigProvider(array());
        $configProvider->getWidgetConfig('not found config');
    }

    /**
     * @expectedException \Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException
     */
    public function testGetDashboardConfigHasNoKeyException()
    {
        $configProvider = new ConfigProvider(array());
        $configProvider->getDashboardConfig('not found config');
    }
}
