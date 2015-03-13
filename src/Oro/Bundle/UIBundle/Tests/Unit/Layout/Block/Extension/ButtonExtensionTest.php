<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Block\Extension;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\BlockView;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\ButtonType;
use Oro\Bundle\UIBundle\Layout\Block\Extension\ButtonExtension;

class ButtonExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ButtonExtension */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new ButtonExtension();
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(ButtonType::NAME, $this->extension->getExtendedType());
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testSetDefaultOptions($options, $expectedOptions)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(['type' => 'button', 'action' => 'none']);
        $this->extension->setDefaultOptions($resolver);
        $actual = $resolver->resolve($options);
        $this->assertEquals($expectedOptions, $actual);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function optionsDataProvider()
    {
        return [
            'empty'                                            => [
                [],
                [
                    'type'   => 'button',
                    'action' => 'none',
                    'text'   => null
                ]
            ],
            'with_path_and_route'                              => [
                [
                    'path'                 => 'http://example.com',
                    'route_name'           => 'test_route',
                    'route_parameters'     => ['foo' => 'bar'],
                    'with_page_parameters' => true
                ],
                [
                    'path'                 => 'http://example.com',
                    'route_name'           => 'test_route',
                    'route_parameters'     => ['foo' => 'bar'],
                    'with_page_parameters' => true,
                    'type'                 => 'button',
                    'action'               => 'none',
                    'text'                 => null
                ]
            ],
            'cancel_action'                                    => [
                ['action' => 'cancel'],
                [
                    'type'   => 'link',
                    'action' => 'cancel',
                    'text'   => 'Cancel'
                ]
            ],
            'create_action_with_entity_label'                  => [
                ['action' => 'create', 'entity_label' => 'test_entity'],
                [
                    'type'         => 'link',
                    'action'       => 'create',
                    'text'         => [
                        'label'      => 'oro.ui.create_entity',
                        'parameters' => [
                            '%entityName%' => ['label' => 'test_entity']
                        ]
                    ],
                    'entity_label' => 'test_entity'
                ]
            ],
            'create_action_with_non_translatable_entity_label' => [
                ['action' => 'create', 'entity_label' => ['label' => 'test_entity', 'translatable' => false]],
                [
                    'type'         => 'link',
                    'action'       => 'create',
                    'text'         => [
                        'label'      => 'oro.ui.create_entity',
                        'parameters' => [
                            '%entityName%' => ['label' => 'test_entity', 'translatable' => false]
                        ]
                    ],
                    'entity_label' => ['label' => 'test_entity', 'translatable' => false]
                ]
            ],
            'edit_action'                                      => [
                ['action' => 'edit'],
                [
                    'type'   => 'link',
                    'action' => 'edit',
                    'text'   => 'oro.ui.edit'
                ]
            ],
            'edit_action_with_entity_label'                    => [
                ['action' => 'edit', 'entity_label' => 'test_entity'],
                [
                    'type'         => 'link',
                    'action'       => 'edit',
                    'text'         => [
                        'label'      => 'oro.ui.edit_entity',
                        'parameters' => [
                            '%entityName%' => ['label' => 'test_entity']
                        ]
                    ],
                    'entity_label' => 'test_entity'
                ]
            ],
            'edit_action_with_non_translatable_entity_label'   => [
                ['action' => 'edit', 'entity_label' => ['label' => 'test_entity', 'translatable' => false]],
                [
                    'type'         => 'link',
                    'action'       => 'edit',
                    'text'         => [
                        'label'      => 'oro.ui.edit_entity',
                        'parameters' => [
                            '%entityName%' => ['label' => 'test_entity', 'translatable' => false]
                        ]
                    ],
                    'entity_label' => ['label' => 'test_entity', 'translatable' => false]
                ]
            ],
            'delete_action'                                    => [
                ['action' => 'delete'],
                [
                    'type'   => 'link',
                    'action' => 'delete',
                    'text'   => 'oro.ui.delete'
                ]
            ],
            'delete_action_with_entity_label'                  => [
                ['action' => 'delete', 'entity_label' => 'test_entity'],
                [
                    'type'         => 'link',
                    'action'       => 'delete',
                    'text'         => [
                        'label'      => 'oro.ui.delete_entity',
                        'parameters' => [
                            '%entityName%' => ['label' => 'test_entity']
                        ]
                    ],
                    'entity_label' => 'test_entity'
                ]
            ],
            'delete_action_with_non_translatable_entity_label' => [
                ['action' => 'delete', 'entity_label' => ['label' => 'test_entity', 'translatable' => false]],
                [
                    'type'         => 'link',
                    'action'       => 'delete',
                    'text'         => [
                        'label'      => 'oro.ui.delete_entity',
                        'parameters' => [
                            '%entityName%' => ['label' => 'test_entity', 'translatable' => false]
                        ]
                    ],
                    'entity_label' => ['label' => 'test_entity', 'translatable' => false]
                ]
            ],
            'create_action'                                    => [
                ['action' => 'create'],
                [
                    'type'   => 'link',
                    'action' => 'create',
                    'text'   => 'oro.ui.create'
                ]
            ],
            'save_action'                                      => [
                ['action' => 'save'],
                [
                    'type'   => 'button',
                    'action' => 'save',
                    'text'   => 'Save'
                ]
            ],
            'save_and_close_action'                            => [
                ['action' => 'save_and_close'],
                [
                    'type'   => 'button',
                    'action' => 'save_and_close',
                    'text'   => 'Save and Close'
                ]
            ]
        ];
    }

    /**
     * @dataProvider buildViewOptionsDataProvider
     */
    public function testBuildView($options, $expectedVars)
    {
        $view  = new BlockView();
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $this->extension->buildView($view, $block, $options);
        unset($view->vars['attr']);
        $this->assertEquals($expectedVars, $view->vars);
    }

    public function buildViewOptionsDataProvider()
    {
        return [
            [
                ['type' => 'button'],
                []
            ],
            [
                [
                    'type' => 'link'
                ],
                [
                    'with_page_parameters' => false
                ]
            ],
            [
                [
                    'type'                 => 'link',
                    'path'                 => 'http://example.com',
                    'with_page_parameters' => true
                ],
                [
                    'path'                 => 'http://example.com',
                    'with_page_parameters' => true
                ]
            ],
            [
                [
                    'type'                 => 'link',
                    'route_name'           => 'test_route',
                    'with_page_parameters' => true
                ],
                [
                    'route_name'           => 'test_route',
                    'route_parameters'     => [],
                    'with_page_parameters' => true
                ]
            ],
            [
                [
                    'type'                 => 'link',
                    'route_name'           => 'test_route',
                    'route_parameters'     => ['foo' => 'bar'],
                    'with_page_parameters' => true
                ],
                [
                    'route_name'           => 'test_route',
                    'route_parameters'     => ['foo' => 'bar'],
                    'with_page_parameters' => true
                ]
            ]
        ];
    }
}
