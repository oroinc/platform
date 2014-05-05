<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model;

use Oro\Bundle\ChartBundle\Model\ConfigProvider;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigs()
    {
        $expected = array('chart_name' => array('config' => 'value'));
        $configProvider = new ConfigProvider($expected);
        $actual = $configProvider->getConfigs();
        $this->assertEquals($expected, $actual);
    }

    public function testHasConfig()
    {
        $chartName = 'chart_name';
        $configs = array($chartName => array('config' => 'value'));
        $noConfigName = 'no config chart name';
        $configProvider = new ConfigProvider($configs);
        $result = $configProvider->hasChartConfig($noConfigName);
        $this->assertFalse($result);
        $result = $configProvider->hasChartConfig($chartName);
        $this->assertTrue($result);
    }

    public function testGetChartConfig()
    {
        $expected = array('config' => 'value');
        $chartName = 'chart_name';
        $config = array($chartName => $expected);
        $configProvider = new ConfigProvider($config);
        $actual = $configProvider->getChartConfig($chartName);
        $this->assertEquals($expected, $actual);
    }

    public function testGetChartConfigs()
    {
        $expected = array('chart_name' => array('config' => 'value'));
        $configProvider = new ConfigProvider($expected);
        $configs = $configProvider->getChartConfigs();
        $this->assertEquals($expected, $configs);
    }

    /**
     * @expectedException \Oro\Bundle\ChartBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Can't find configuration for chart: any name
     */
    public function testGetChartConfigThrowAnException()
    {
        $configProvider = new ConfigProvider(array());
        $configProvider->getChartConfig('any name');
    }
}
