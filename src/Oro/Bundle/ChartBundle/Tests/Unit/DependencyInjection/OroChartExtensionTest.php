<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\DependencyInjection;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Bundle\ChartBundle\DependencyInjection\OroChartExtension;

class OroChartExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroChartExtension
     */
    protected $target;

    /**
     * @var array
     */
    protected $bundlesBackup;

    protected function setUp()
    {
        $this->bundlesBackup = CumulativeResourceManager::getInstance()->getBundles();

        $this->target = new OroChartExtension();
    }

    protected function tearDown()
    {
        CumulativeResourceManager::getInstance()->setBundles($this->bundlesBackup);
    }

    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad(array $bundles, array $configs, array $expectedConfiguration)
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        CumulativeResourceManager::getInstance()->setBundles($bundles);
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $definition->expects($this->once())->method('replaceArgument')->with(
            0,
            $this->callback(
                //not use equalTo because it is not check items position
                function ($actualConfiguration) use ($expectedConfiguration) {
                    $this->assertSame($expectedConfiguration, $actualConfiguration);
                    return true;
                }
            )
        );
        $container->expects($this->once())
            ->method('getDefinition')
            ->with('oro_chart.config_provider')
            ->will($this->returnValue($definition));
        $this->target->load($configs, $container);
    }

    public function loadDataProvider()
    {
        $firstBundle = 'Oro\Bundle\ChartBundle\Tests\Unit\Fixtures\FirstTestBundle\FirstTestBundle';
        $secondBundle = 'Oro\Bundle\ChartBundle\Tests\Unit\Fixtures\SecondTestBundle\SecondTestBundle';

        return array(
            array(
                'bundles' => array($firstBundle, $secondBundle),
                'configs' => array(array()),
                'expectedConfiguration' => array(),
            ),
        );
    }
}
