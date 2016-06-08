<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Builder;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Forms;

use Oro\Bundle\FormBundle\Form\Builder\DataBlockBuilder;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;

class DataBlockBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormFactory
     */
    private $factory;

    /**
     * @var DataBlockBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->factory = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new DataBlockExtension())
            ->getFormFactory();

        $templateRenderer = $this->getMock('Oro\Bundle\FormBundle\Form\Builder\TemplateRendererInterface');
        $templateRenderer->expects($this->any())
            ->method('render')
            ->will($this->returnArgument(0));

        $this->builder = new DataBlockBuilder($templateRenderer, 'form');
    }

    public function testOverride()
    {
        $formOptions    = [
            'block_config' => [
                'first'  => [
                    'priority'  => 20,
                    'subblocks' => [
                        'first'  => [
                            'priority' => 20
                        ],
                        'second' => [
                            'priority' => 10
                        ],
                    ],
                ],
                'second' => [
                    'priority' => 10,
                ]
            ]
        ];
        $formItems      = [
            'item1' => ['block' => 'first', 'subblock' => 'first'],
            'item2' => [
                'block' => 'first',
                'subblock' => 'second',
                'block_config' => [
                    'first' => [
                        'subblocks' => [
                            'second' => [
                                'priority' => 30
                            ],
                        ],
                    ]
                ]
            ],
            'item3' => [
                'block'        => 'second',
                'subblock'     => 'first',
                'block_config' => [
                    'second' => [
                        'title' => 'Changed Second'
                    ]
                ]
            ],
        ];
        $expectedBlocks = [
            'First'  => [
                'second' => ['item2'],
                'first'  => ['item1'],
            ],
            'Changed Second' => [
                'first' => ['item3'],
            ],
        ];

        $formBuilder = $this->factory->createNamedBuilder('test', 'form', null, $formOptions);
        $this->buildForm($formBuilder, $formItems);
        $formView = $formBuilder->getForm()->createView();

        $result = $this->builder->build($formView);

        $expected = $this->getBlocks($expectedBlocks);
        $this->assertEquals($expected, $result->toArray());
    }

    /**
     * @dataProvider layoutProvider
     */
    public function testLayout($formOptions, $formItems, $expectedBlocks)
    {
        $formBuilder = $this->factory->createNamedBuilder('test', 'form', null, $formOptions);
        $this->buildForm($formBuilder, $formItems);
        $formView = $formBuilder->getForm()->createView();

        $result = $this->builder->build($formView);

        $expected = $this->getBlocks($expectedBlocks);
        $this->assertEquals($expected, $result->toArray());
    }

    public function layoutProvider()
    {
        return [
            [
                [
                    'block_config' => [
                        'first'  => [
                            'priority'  => 1,
                            'subblocks' => [
                                'first'  => null,
                                'second' => null,
                            ],
                        ],
                        'second' => [
                            'priority' => 2,
                        ]
                    ]
                ],
                [
                    'item1' => ['block' => 'first', 'subblock' => 'second'],
                    'item2' => ['block' => 'first'],
                    'item3' => ['block' => 'second'],
                    'item4' => ['block' => 'third'],
                    'item5' => ['block' => 'third', 'subblock' => 'first'],
                    'item6' => ['block' => 'third', 'subblock' => 'first'],
                    'item7' => ['block' => 'first', 'subblock' => 'first'],
                    'item8' => [],
                ],
                [
                    'Second' => [
                        'item3__subblock' => ['item3'],
                    ],
                    'First'  => [
                        'first'  => ['item2', 'item7'],
                        'second' => ['item1'],
                    ],
                    'Third'  => [
                        'item4__subblock' => ['item4'],
                        'first'           => ['item5', 'item6'],
                    ],
                ]
            ],
        ];
    }

    protected function buildForm(FormBuilderInterface $formBuilder, array $items = [])
    {
        foreach ($items as $name => $options) {
            $formBuilder->add($name, null, $options);
        }
    }

    protected function getBlocks(array $blocks = [])
    {
        $result = [];
        foreach ($blocks as $title => $subBlocks) {
            $result[] = $this->getBlock($title, $subBlocks);
        }

        return $result;
    }

    protected function getBlock($title, array $subBlocks = [])
    {
        $sb = [];
        foreach ($subBlocks as $code => $itemNames) {
            $sb[] = $this->getSubBlock($code, $itemNames);
        }

        return [
            'title'       => $title,
            'description' => null,
            'class'       => null,
            'subblocks'   => $sb
        ];
    }

    protected function getSubBlock($code, array $itemNames = [])
    {
        return [
            'code'        => $code,
            'title'       => null,
            'description' => null,
            'data'        => $this->getData($itemNames),
            'useSpan'     => true,
            'tooltip'     => null
        ];
    }

    protected function getData(array $itemNames = [])
    {
        $data = [];
        foreach ($itemNames as $itemName) {
            $data[] = sprintf('{{ form_row(form.children[\'%s\']) }}', $itemName);
        }

        return $data;
    }
}
