<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\UIBundle\DependencyInjection\OroUIExtension;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\BarBundle\BarBundle;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\FooBundle\FooBundle;

use Oro\Component\Config\CumulativeResourceManager;

class OroUIExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testLoad()
    {
        $bundle1 = new BarBundle();
        $bundle2 = new FooBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(
                [
                    $bundle1->getName() => get_class($bundle1),
                    $bundle2->getName() => get_class($bundle2),
                ]
            );

        $container  = new ContainerBuilder();

        $extensionConfig = array(
            array(
                'placeholders_items' => array(
                    'test_block'       => array(
                        'items' => array(
                            'item1'          => array(
                                'remove' => true
                            ),
                            'item4'          => array(
                                'order' => 15
                            ),
                            'item7'          => array(
                                'order' => -5
                            ),
                            'new_empty_item' => array(
                                'order' => 100
                            ),
                            'new_item'       => array(
                                'template' => 'test_template',
                                'order'    => 5
                            ),
                        )
                    ),
                )
            )
        );

        $extension = new OroUIExtension();
        $extension->load($extensionConfig, $container);

        $palaceholders = $container->getParameter('oro_ui.placeholders');
        $this->assertEquals(
            [
                'test_block'       => array(
                    'items' => array(
                        array(
                            'name'   => 'item6',
                            'action' => 'TestBundle:Test:test6',
                            'order'  => -10
                        ),
                        array(
                            'name'   => 'item7',
                            'action' => 'TestBundle:Test:test7',
                            'order'  => -5
                        ),
                        array(
                            'name'   => 'item2',
                            'action' => 'TestBundle:Test:test2',
                            'order'  => 0
                        ),
                        array(
                            'name'                  => 'item3',
                            'action'                => 'TestBundle:Test:test3',
                            'order'                 => 0
                        ),
                        array(
                            'name'     => 'new_item',
                            'template' => 'test_template',
                            'order'    => 5
                        ),
                        array(
                            'name'   => 'item4',
                            'action' => 'TestBundle:Test:test4',
                            'order'  => 15
                        ),
                        array(
                            'name'   => 'item5',
                            'action' => 'TestBundle:Test:test5',
                            'order'  => 20
                        ),
                    )
                ),
                'test_merge_block' => array(
                    'items' => array(
                        array(
                            'name'     => 'item1',
                            'template' => 'TestBundle::test.html.twig',
                            'order'    => 10
                        )
                    ),
                ),
                'empty_block'      => array(
                    'items' => array()
                ),
            ],
            $palaceholders
        );
    }
}
