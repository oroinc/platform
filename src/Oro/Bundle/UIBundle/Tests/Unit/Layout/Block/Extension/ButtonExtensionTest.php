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
        $resolver
            ->setOptional(['attr', 'text'])
            ->setDefaults(['type' => 'button', 'action' => 'none']);
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
                    'type'            => 'button',
                    'action'          => 'none',
                    'text'            => null,
                    'attr'            => [],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'with_all_options'                                 => [
                [
                    'path'                      => 'test_path',
                    'route_name'                => 'test_route',
                    'route_parameters'          => ['foo' => 'bar'],
                    'redirect_path'             => 'test_redirect_path',
                    'redirect_route_name'       => 'test_redirect_route',
                    'redirect_route_parameters' => ['foo_redirect' => 'bar'],
                    'with_page_parameters'      => true,
                    'text'                      => 'test_text',
                    'entity_label'              => 'test_entity_label',
                    'entity_id'                 => 123,
                    'confirm_message'           => 'test_confirm_message',
                    'success_message'           => 'test_success_message'
                ],
                [
                    'type'                      => 'button',
                    'action'                    => 'none',
                    'path'                      => 'test_path',
                    'route_name'                => 'test_route',
                    'route_parameters'          => ['foo' => 'bar'],
                    'redirect_path'             => 'test_redirect_path',
                    'redirect_route_name'       => 'test_redirect_route',
                    'redirect_route_parameters' => ['foo_redirect' => 'bar'],
                    'with_page_parameters'      => true,
                    'text'                      => 'test_text',
                    'entity_label'              => 'test_entity_label',
                    'entity_id'                 => 123,
                    'attr'                      => [],
                    'confirm_message'           => 'test_confirm_message',
                    'success_message'           => 'test_success_message'
                ]
            ],
            'cancel_action'                                    => [
                ['action' => 'cancel'],
                [
                    'type'            => 'link',
                    'action'          => 'cancel',
                    'text'            => 'Cancel',
                    'attr'            => [],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'cancel_action_with_custom_text'                   => [
                ['action' => 'cancel', 'text' => 'test_text'],
                [
                    'type'            => 'link',
                    'action'          => 'cancel',
                    'text'            => 'test_text',
                    'attr'            => [],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'create_action'                                    => [
                ['action' => 'create'],
                [
                    'type'            => 'link',
                    'action'          => 'create',
                    'text'            => 'oro.ui.create',
                    'attr'            => [],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'create_action_with_custom_text'                   => [
                ['action' => 'create', 'text' => 'test_text'],
                [
                    'type'            => 'link',
                    'action'          => 'create',
                    'text'            => 'test_text',
                    'attr'            => [],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'create_action_with_entity_label'                  => [
                ['action' => 'create', 'entity_label' => 'test_entity'],
                [
                    'type'            => 'link',
                    'action'          => 'create',
                    'text'            => [
                        'label'      => 'oro.ui.create_entity',
                        'parameters' => [
                            '%entityName%' => ['label' => 'test_entity']
                        ]
                    ],
                    'entity_label'    => 'test_entity',
                    'attr'            => [],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'create_action_with_non_translatable_entity_label' => [
                ['action' => 'create', 'entity_label' => ['label' => 'test_entity', 'translatable' => false]],
                [
                    'type'            => 'link',
                    'action'          => 'create',
                    'text'            => [
                        'label'      => 'oro.ui.create_entity',
                        'parameters' => [
                            '%entityName%' => ['label' => 'test_entity', 'translatable' => false]
                        ]
                    ],
                    'entity_label'    => ['label' => 'test_entity', 'translatable' => false],
                    'attr'            => [],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'edit_action'                                      => [
                ['action' => 'edit'],
                [
                    'type'            => 'link',
                    'action'          => 'edit',
                    'text'            => 'oro.ui.edit',
                    'attr'            => [
                        'title' => 'oro.ui.edit'
                    ],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'edit_action_with_custom_text'                     => [
                ['action' => 'edit', 'text' => 'test_text', 'attr' => ['title' => 'test_title']],
                [
                    'type'            => 'link',
                    'action'          => 'edit',
                    'text'            => 'test_text',
                    'attr'            => [
                        'title' => 'test_title'
                    ],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'edit_action_with_entity_label'                    => [
                ['action' => 'edit', 'entity_label' => 'test_entity'],
                [
                    'type'            => 'link',
                    'action'          => 'edit',
                    'text'            => 'oro.ui.edit',
                    'entity_label'    => 'test_entity',
                    'attr'            => [
                        'title' => [
                            'label'      => 'oro.ui.edit_entity',
                            'parameters' => [
                                '%entityName%' => ['label' => 'test_entity']
                            ]
                        ]
                    ],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'edit_action_with_non_translatable_entity_label'   => [
                ['action' => 'edit', 'entity_label' => ['label' => 'test_entity', 'translatable' => false]],
                [
                    'type'            => 'link',
                    'action'          => 'edit',
                    'text'            => 'oro.ui.edit',
                    'entity_label'    => ['label' => 'test_entity', 'translatable' => false],
                    'attr'            => [
                        'title' => [
                            'label'      => 'oro.ui.edit_entity',
                            'parameters' => [
                                '%entityName%' => ['label' => 'test_entity', 'translatable' => false]
                            ]
                        ]
                    ],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'delete_action'                                    => [
                ['action' => 'delete'],
                [
                    'type'            => 'link',
                    'action'          => 'delete',
                    'text'            => 'oro.ui.delete',
                    'attr'            => [
                        'title' => 'oro.ui.delete'
                    ],
                    'confirm_message' => [
                        'label'      => 'oro.ui.delete_confirm',
                        'parameters' => [
                            '%entity_label%' => ['label' => 'oro.ui.item']
                        ]
                    ],
                    'success_message' => [
                        'label'      => 'oro.ui.delete_message',
                        'parameters' => [
                            '%entity_label%' => ['label' => 'oro.ui.item']
                        ]
                    ]
                ]
            ],
            'delete_action_with_custom_text'                   => [
                [
                    'action'          => 'delete',
                    'text'            => 'test_text',
                    'attr'            => ['title' => 'test_title'],
                    'confirm_message' => 'test_confirm_message',
                    'success_message' => 'test_success_message'
                ],
                [
                    'type'            => 'link',
                    'action'          => 'delete',
                    'text'            => 'test_text',
                    'attr'            => [
                        'title' => 'test_title'
                    ],
                    'confirm_message' => 'test_confirm_message',
                    'success_message' => 'test_success_message'
                ]
            ],
            'delete_action_with_entity_label'                  => [
                ['action' => 'delete', 'entity_label' => 'test_entity'],
                [
                    'type'            => 'link',
                    'action'          => 'delete',
                    'text'            => 'oro.ui.delete',
                    'entity_label'    => 'test_entity',
                    'attr'            => [
                        'title' => [
                            'label'      => 'oro.ui.delete_entity',
                            'parameters' => [
                                '%entityName%' => ['label' => 'test_entity']
                            ]
                        ]
                    ],
                    'confirm_message' => [
                        'label'      => 'oro.ui.delete_confirm',
                        'parameters' => [
                            '%entity_label%' => ['label' => 'test_entity']
                        ]
                    ],
                    'success_message' => [
                        'label'      => 'oro.ui.delete_message',
                        'parameters' => [
                            '%entity_label%' => ['label' => 'test_entity']
                        ]
                    ]
                ]
            ],
            'delete_action_with_non_translatable_entity_label' => [
                ['action' => 'delete', 'entity_label' => ['label' => 'test_entity', 'translatable' => false]],
                [
                    'type'            => 'link',
                    'action'          => 'delete',
                    'text'            => 'oro.ui.delete',
                    'entity_label'    => ['label' => 'test_entity', 'translatable' => false],
                    'attr'            => [
                        'title' => [
                            'label'      => 'oro.ui.delete_entity',
                            'parameters' => [
                                '%entityName%' => ['label' => 'test_entity', 'translatable' => false]
                            ]
                        ]
                    ],
                    'confirm_message' => [
                        'label'      => 'oro.ui.delete_confirm',
                        'parameters' => [
                            '%entity_label%' => ['label' => 'test_entity', 'translatable' => false]
                        ]
                    ],
                    'success_message' => [
                        'label'      => 'oro.ui.delete_message',
                        'parameters' => [
                            '%entity_label%' => ['label' => 'test_entity', 'translatable' => false]
                        ]
                    ]
                ]
            ],
            'save_action'                                      => [
                ['action' => 'save'],
                [
                    'type'            => 'button',
                    'action'          => 'save',
                    'text'            => 'Save',
                    'attr'            => [],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'save_action_with_custom_text'                     => [
                ['action' => 'save', 'text' => 'test_text'],
                [
                    'type'            => 'button',
                    'action'          => 'save',
                    'text'            => 'test_text',
                    'attr'            => [],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'save_and_close_action'                            => [
                ['action' => 'save_and_close'],
                [
                    'type'            => 'button',
                    'action'          => 'save_and_close',
                    'text'            => 'Save and Close',
                    'attr'            => [],
                    'confirm_message' => null,
                    'success_message' => null
                ]
            ],
            'save_and_close_action_with_custom_text'           => [
                ['action' => 'save_and_close', 'text' => 'test_text'],
                [
                    'type'            => 'button',
                    'action'          => 'save_and_close',
                    'text'            => 'test_text',
                    'attr'            => [],
                    'confirm_message' => null,
                    'success_message' => null
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
                    'type'                 => 'link',
                    'path'                 => 'test_path',
                    'with_page_parameters' => true
                ],
                [
                    'path'                 => 'test_path',
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
            ],
            [
                [
                    'type'                 => 'link',
                    'redirect_path'        => 'test_path',
                    'with_page_parameters' => true
                ],
                [
                    'redirect_path'        => 'test_path',
                    'with_page_parameters' => true
                ]
            ],
            [
                [
                    'type'                 => 'link',
                    'redirect_route_name'  => 'test_route',
                    'with_page_parameters' => true
                ],
                [
                    'redirect_route_name'       => 'test_route',
                    'redirect_route_parameters' => [],
                    'with_page_parameters'      => true
                ]
            ],
            [
                [
                    'type'                      => 'link',
                    'redirect_route_name'       => 'test_route',
                    'redirect_route_parameters' => ['foo' => 'bar'],
                    'with_page_parameters'      => true
                ],
                [
                    'redirect_route_name'       => 'test_route',
                    'redirect_route_parameters' => ['foo' => 'bar'],
                    'with_page_parameters'      => true
                ]
            ],
            [
                [
                    'type'            => 'button',
                    'entity_label'    => '',
                    'entity_id'       => '',
                    'confirm_message' => '',
                    'success_message' => ''
                ],
                []
            ],
            [
                [
                    'type'            => 'button',
                    'entity_label'    => 'test_entity_label',
                    'entity_id'       => 'test_entity_id',
                    'confirm_message' => 'test_confirm_message',
                    'success_message' => 'test_success_message'
                ],
                [
                    'entity_label'    => 'test_entity_label',
                    'entity_id'       => 'test_entity_id',
                    'confirm_message' => 'test_confirm_message',
                    'success_message' => 'test_success_message'
                ]
            ]
        ];
    }
}
