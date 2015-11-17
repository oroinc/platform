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

        $container = new ContainerBuilder();

        $extensionConfig = [
            [
                'placeholders'       => [
                    'test_block' => [
                        'items' => [
                            'item1'          => [
                                'remove' => true
                            ],
                            'item4'          => [
                                'order' => 15
                            ],
                            'item7'          => [
                                'order' => -5
                            ],
                            'new_empty_item' => [
                                'order' => 100
                            ],
                            'new_item'       => [
                                'order' => 5
                            ],
                        ]
                    ],
                ],
                'placeholder_items' => [
                    'new_item' => [
                        'template' => 'test_template',
                    ],
                    'new_applicable_string_item' => [
                        'template' => 'test_template',
                        'applicable' => 'test_condition'
                    ],
                    'new_applicable_array_item' => [
                        'template' => 'test_template',
                        'applicable' => ['test_condition1', 'test_condition2']
                    ]
                ]
            ]
        ];

        $extension = new OroUIExtension();
        $extension->load($extensionConfig, $container);

        $palaceholders = $container->getParameter('oro_ui.placeholders');
        $this->assertEquals(
            [
                'placeholders' => [
                    'test_block'       => [
                        'items' => ['item6', 'item7', 'item2', 'item3', 'new_item', 'item4', 'item5', 'new_empty_item']
                    ],
                    'test_merge_block' => [
                        'items' => ['item1']
                    ],
                    'empty_block'      => [
                        'items' => []
                    ],
                ],
                'items'        => [
                    'item1'    => [
                        'template' => 'TestBundle::test.html.twig',
                    ],
                    'item2'    => [
                        'action' => 'TestBundle:Test:test2',
                    ],
                    'item3'    => [
                        'action' => 'TestBundle:Test:test3',
                    ],
                    'item4'    => [
                        'action' => 'TestBundle:Test:test4',
                    ],
                    'item5'    => [
                        'action' => 'TestBundle:Test:test5',
                    ],
                    'item6'    => [
                        'action' => 'TestBundle:Test:test6',
                    ],
                    'item7'    => [
                        'action' => 'TestBundle:Test:test7',
                    ],
                    'new_item' => [
                        'template' => 'test_template',
                    ],
                    'new_applicable_string_item' => [
                        'template' => 'test_template',
                        'applicable' => 'test_condition'
                    ],
                    'new_applicable_array_item' => [
                        'template' => 'test_template',
                        'applicable' => ['test_condition1', 'test_condition2']
                    ]
                ]
            ],
            $palaceholders
        );
    }
}
