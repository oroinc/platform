<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\UIBundle\DependencyInjection\OroUIExtension;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\UnitTestBundle;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroUIExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $bundle = new UnitTestBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle->getName() => get_class($bundle)])
            ->addResourceLoader(
                'OroUIBundle',
                new YamlCumulativeFileLoader('Resources/config/placeholders.yml')
            );

        $container = new ContainerBuilder();

        $extensionConfig = array(
            array(
                'placeholders_items' => array(
                    'top_block' => array(
                        'items' => array(
                            'top_test_template' => array(
                                'remove' => true
                            ),
                            'insert_template' => array(
                                'order' => 100
                            ),
                        )
                    )

                )
            )
        );

        $extension = new OroUIExtension();
        $extension->load($extensionConfig, $container);
    }
}
