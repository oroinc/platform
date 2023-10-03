<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Builder;

use Oro\Bundle\FormBundle\Form\Builder\DataBlockBuilder;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\UIBundle\Twig\Environment;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

class DataBlockBuilderTest extends \PHPUnit\Framework\TestCase
{
    const TEMPLATE_CODE = '{% set parts = formFieldPath|split(' . ') %}
{% set form = parentContext %}
{% for part in parts %}
    {% set form = attribute(form, part) %}
{% endfor %}

{{ form_row(form) }}';
    const TEMPLATE_PATH = '@OroForm/Form/data_block_item.html.twig';
    private FormFactoryInterface $formFactory;

    private DataBlockBuilder $builder;
    private $twig;

    protected function setUp(): void
    {
        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addTypeExtension(new DataBlockExtension())
            ->getFormFactory();

        $this->twig = $this->createMock(Environment::class);

        $this->builder = new DataBlockBuilder($this->twig, ['form' => []], 'form');
    }

    public function testOverride(): void
    {
        $formOptions = [
            'block_config' => [
                'first' => [
                    'priority' => 20,
                    'subblocks' => [
                        'first' => [
                            'priority' => 20,
                        ],
                        'second' => [
                            'priority' => 10,
                        ],
                    ],
                ],
                'second' => [
                    'priority' => 10,
                ],
            ],
        ];
        $formItems = [
            'item1' => ['block' => 'first', 'subblock' => 'first'],
            'item2' => [
                'block' => 'first',
                'subblock' => 'second',
                'block_config' => [
                    'first' => [
                        'subblocks' => [
                            'second' => [
                                'priority' => 30,
                            ],
                        ],
                    ],
                ],
            ],
            'item3' => [
                'block' => 'second',
                'subblock' => 'first',
                'block_config' => [
                    'second' => [
                        'title' => 'Changed Second',
                    ],
                ],
            ],
        ];
        $expectedBlocks = [
            'First' => [
                'second' => ['item2'],
                'first' => ['item1'],
            ],
            'Changed Second' => [
                'first' => ['item3'],
            ],
        ];

        $formBuilder = $this->formFactory->createNamedBuilder('test', FormType::class, null, $formOptions);
        $this->buildForm($formBuilder, $formItems);
        $formView = $formBuilder->getForm()->createView();

        $this->twig
            ->method('render')
            ->withConsecutive(
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => 'form.children.item1'
                    ]
                ],
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => 'form.children.item2'
                    ]
                ],
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => 'form.children.item3'
                    ]
                ]
            )
            ->willReturn(self::TEMPLATE_CODE);

        $result = $this->builder->build($formView);

        $expected = $this->getBlocks($expectedBlocks);
        self::assertEquals($expected, $result->toArray());
    }

    public function testBuildWhenAlreadyRendered(): void
    {
        $formOptions = [
            'block_config' => [
                'first' => [
                    'priority' => 20,
                    'subblocks' => [
                        'first' => [
                            'priority' => 20,
                        ],
                        'second' => [
                            'priority' => 10,
                        ],
                        'third' => [
                            'priority' => 5,
                        ],
                    ],
                ],
            ],
        ];
        $formItems = [
            'item1' => ['block' => 'first', 'subblock' => 'first'],
            'item2' => ['block' => 'first', 'subblock' => 'second'],
        ];
        $expectedBlocks = [
            'First' => [
                'first' => ['item1'],
                'second' => ['item2'],
                'third' => [['item3', 'item3_2']],
            ],
        ];

        $formBuilder = $this->formFactory->createNamedBuilder('test', FormType::class, null, $formOptions);
        $this->buildForm($formBuilder, $formItems);
        $item3FormBuilder = $this->formFactory->createNamedBuilder('item3');
        $item3FormBuilder->add('item3_1', null, ['block' => 'first', 'subblock' => 'third']);
        $item3FormBuilder->add('item3_2', null, ['block' => 'first', 'subblock' => 'third']);
        $formBuilder->add($item3FormBuilder);

        $formView = $formBuilder->getForm()->createView();
        $formView['item3']['item3_1']->setRendered();

        $this->twig
            ->method('render')
            ->withConsecutive(
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => 'form.children.item1'
                    ]
                ],
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => 'form.children.item2'
                    ]
                ],
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => 'form.children.item3.children.item3_2'
                    ]
                ]
            )
            ->willReturn(self::TEMPLATE_CODE);


        $result = $this->builder->build($formView);

        $expected = $this->getBlocks($expectedBlocks);
        self::assertEquals($expected, $result->toArray());
    }

    /**
     * @dataProvider layoutProvider
     */
    public function testLayout($formOptions, $formItems, $expectedBlocks): void
    {
        $this->twig
            ->method('render')
            ->withConsecutive(
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => 'form.children.item1'
                    ]
                ],
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => 'form.children.item2'
                    ]
                ],
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => 'form.children.item3'
                    ]
                ],
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => 'form.children.item4'
                    ]
                ],
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => 'form.children.item5'
                    ]
                ],
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => 'form.children.item6'
                    ]
                ],
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => 'form.children.item7'
                    ]
                ],
                [
                    self::TEMPLATE_PATH,
                    [
                        'parentContext' => ['form' => []],
                        'formFieldPath' => ''
                    ]
                ]
            )
            ->willReturn(self::TEMPLATE_CODE);
        $formBuilder = $this->formFactory->createNamedBuilder('test', FormType::class, null, $formOptions);
        $this->buildForm($formBuilder, $formItems);
        $formView = $formBuilder->getForm()->createView();

        $result = $this->builder->build($formView);

        $expected = $this->getBlocks($expectedBlocks);
        self::assertEquals($expected, $result->toArray());
    }

    public function layoutProvider(): array
    {
        return [
            [
                [
                    'block_config' => [
                        'first' => [
                            'priority' => 1,
                            'subblocks' => [
                                'first' => null,
                                'second' => null,
                            ],
                        ],
                        'second' => [
                            'priority' => 2,
                        ],
                    ],
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
                    'First' => [
                        'first' => ['item2', 'item7'],
                        'second' => ['item1'],
                    ],
                    'Third' => [
                        'item4__subblock' => ['item4'],
                        'first' => ['item5', 'item6'],
                    ],
                ],
            ],
        ];
    }

    private function buildForm(FormBuilderInterface $formBuilder, array $items = []): void
    {
        foreach ($items as $child => $options) {
            $formBuilder->add($child, null, $options);
        }
    }

    private function getBlocks(array $blocks = []): array
    {
        $result = [];
        foreach ($blocks as $title => $subBlocks) {
            $result[] = $this->getBlock($title, $subBlocks);
        }

        return $result;
    }

    private function getBlock($title, array $subBlocks = []): array
    {
        $sb = [];
        foreach ($subBlocks as $code => $itemNames) {
            $sb[] = $this->getSubBlock($code, $itemNames);
        }

        return [
            'title' => $title,
            'description' => null,
            'class' => null,
            'subblocks' => $sb,
        ];
    }

    private function getSubBlock($code, array $itemNames = []): array
    {
        return [
            'code' => $code,
            'title' => null,
            'description' => null,
            'descriptionStyle' => null,
            'data' => $this->getData($itemNames),
            'useSpan' => true,
            'tooltip' => null,
        ];
    }

    private function getData(array $itemNames = []): array
    {
        $data = [];
        foreach ($itemNames as $itemName) {
            $itemName = (array)$itemName;
            $data[end($itemName)] = self::TEMPLATE_CODE;
        }

        return $data;
    }
}
