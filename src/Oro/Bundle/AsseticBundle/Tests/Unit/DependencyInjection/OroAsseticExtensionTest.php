<?php

namespace Oro\Bundle\AsseticBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AsseticBundle\DependencyInjection\OroAsseticExtension;
use Oro\Bundle\AsseticBundle\Tests\Unit\Fixtures;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroAsseticExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider loadDataProvider
     * @param array $configs
     * @param array $expectedBundles
     * @param array $expectedConfiguration
     * @param array $asseticBundles
     * @param array $expectedAsseticBundles
     */
    public function testLoad(
        array $configs,
        array $expectedBundles,
        array $expectedConfiguration,
        array $asseticBundles,
        array $expectedAsseticBundles
    ) {
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles($expectedBundles);

        $extension = new OroAsseticExtension();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', $expectedBundles);
        $container->setParameter('assetic.bundles', $asseticBundles);

        $extension->load($configs, $container);

        $this->assertEquals($expectedConfiguration, $container->getParameter('oro_assetic.raw_configuration'));
        $this->assertEquals($expectedAsseticBundles, $container->getParameter('assetic.bundles'));

        $this->assertNotNull($container->getDefinition('oro_assetic.configuration'));
        $this->assertNotNull($container->getDefinition('oro_assetic.twig.extension'));
    }

    /**
     * @return array
     */
    public function loadDataProvider()
    {
        $bundle = new Fixtures\TestBundle();

        return [
            'minimal' => [
                'configs' => [
                    []
                ],
                'expectedBundles' => [],
                'expectedConfiguration' => [
                    'css_debug_groups' => [],
                    'css_debug_all' => false,
                    'css' => []
                ],
                'asseticBundles' => [],
                'expectedAsseticBundles' => []
            ],
            'full' => [
                'configs' => [
                    [
                        'css_debug' => ['css_group'],
                        'css_debug_all' => true,
                        'excluded_bundles' => ['Bundle2']
                    ]
                ],
                'expectedBundles' => [$bundle->getName() => get_class($bundle)],
                'expectedConfiguration' => [
                    'css_debug_groups' => ['css_group'],
                    'css_debug_all' => true,
                    'css' => [
                        'css_group' => [
                            'first.css',
                            'second.css'
                        ]
                    ]
                ],
                'asseticBundles' => ['Bundle1', 'Bundle2'],
                'expectedAsseticBundles' => ['Bundle1']
            ],
        ];
    }
}
