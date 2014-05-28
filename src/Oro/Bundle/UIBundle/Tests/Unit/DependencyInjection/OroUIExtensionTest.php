<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\UIBundle\DependencyInjection\OroUIExtension;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\BarBundle\BarBundle;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\FooBundle\FooBundle;

use Oro\Component\Config\CumulativeResourceManager;

class OroUIExtensionTest extends \PHPUnit_Framework_TestCase
{
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

        $container = new ContainerBuilder();

        $extensionConfig = array(
            array(
                'placeholders_items' => array(
                    'test_block' => array(
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
                    )

                )
            )
        );

        $extension = new OroUIExtension();
        $extension->load($extensionConfig, $container);

        $this->assertEquals(
            [
                'test_block'       => array(
                    'items' => array(
                        'item6'    => array(
                            'action' => 'TestBundle:Test:test6',
                            'order'  => -10
                        ),
                        'item7'    => array(
                            'action' => 'TestBundle:Test:test7',
                            'order'  => -5
                        ),
                        'item2'    => array(
                            'action' => 'TestBundle:Test:test2',
                            'order'  => 0
                        ),
                        'item3'    => array(
                            'action'                => 'TestBundle:Test:test3',
                            'order'                 => 0,
                            'attribute_instance_of' => array('entity', '%oro_user.entity.class%')
                        ),
                        'new_item' => array(
                            'template' => 'test_template',
                            'order'    => 5
                        ),
                        'item4'    => array(
                            'action' => 'TestBundle:Test:test4',
                            'order'  => 15
                        ),
                        'item5'    => array(
                            'action' => 'TestBundle:Test:test5',
                            'order'  => 20
                        ),
                    )
                ),
                'test_merge_block' => array(
                    'items' => array(
                        'item1' => array(
                            'template' => 'TestBundle::test.html.twig',
                            'order'    => 10
                        )
                    ),
                ),
                'empty_block'      => array(
                    'items' => array()
                )
            ],
            $container->getParameter('oro_ui.placeholders')
        );
    }
}
