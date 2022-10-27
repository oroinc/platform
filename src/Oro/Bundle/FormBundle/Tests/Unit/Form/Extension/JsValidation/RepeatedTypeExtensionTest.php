<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\RepeatedTypeExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

class RepeatedTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var RepeatedTypeExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new RepeatedTypeExtension();
    }

    /**
     * @dataProvider finishViewDataProvider
     */
    public function testFinishView(
        FormView $view,
        array $options,
        array $expectedVars,
        array $expectedChildrenVars
    ) {
        $form = $this->createMock(FormInterface::class);

        $this->extension->finishView($view, $form, $options);

        $this->assertEquals($expectedVars, $view->vars);
        $this->assertSameSize($expectedChildrenVars, $view->children);

        foreach ($expectedChildrenVars as $childName => $expectedVars) {
            $this->assertArrayHasKey($childName, $view->children);
            $this->assertEquals($expectedVars, $view->children[$childName]->vars);
        }
    }

    public function finishViewDataProvider(): array
    {
        return [
            'default' => [
                'formView' => $this->createView(
                    [],
                    [
                        'first' => $this->createView(),
                        'second' => $this->createView(),
                    ]
                ),
                'options' => [
                    'first_name' => 'first',
                    'second_name' => 'second',
                    'invalid_message' => 'Some invalid message',
                    'invalid_message_parameters' => [1],
                ],
                'expectedVars' => [
                    'value' => null,
                    'attr' => [],
                ],
                'expectedChildrenVars' => [
                    'first' => [
                        'value' => null,
                        'attr' => []
                    ],
                    'second' => [
                        'value' => null,
                        'attr' => [
                            'data-validation' => json_encode(
                                [
                                    'Repeated' => [
                                        'first_name' => 'first',
                                        'second_name' => 'second',
                                        'invalid_message' => 'Some invalid message',
                                        'invalid_message_parameters' => [1],
                                    ]
                                ]
                            )
                        ]
                    ]
                ]
            ],
            'copy_attr_to_first_children' => [
                'formView' => $this->createView(
                    [
                        'attr' => [
                            'data-validation' => json_encode(['NotBlank' => []])
                        ],
                    ],
                    [
                        'first' => $this->createView(),
                        'second' => $this->createView(),
                    ]
                ),
                'options' => [
                    'first_name' => 'first',
                    'second_name' => 'second',
                    'invalid_message' => 'Some invalid message',
                    'invalid_message_parameters' => [1],
                ],
                'expectedVars' => [
                    'value' => null,
                    'attr' => [],
                ],
                'expectedChildrenVars' => [
                    'first' => [
                        'value' => null,
                        'attr' => [
                            'data-validation' => json_encode(['NotBlank' => []])
                        ]
                    ],
                    'second' => [
                        'value' => null,
                        'attr' => [
                            'data-validation' => json_encode(
                                [
                                    'Repeated' => [
                                        'first_name' => 'first',
                                        'second_name' => 'second',
                                        'invalid_message' => 'Some invalid message',
                                        'invalid_message_parameters' => [1],
                                    ]
                                ]
                            )
                        ]
                    ]
                ]
            ]
        ];
    }

    private function createView(array $vars = [], array $children = [], FormView $parent = null): FormView
    {
        $result = new FormView();
        $result->vars = array_merge_recursive($result->vars, $vars);
        $result->children = $children;
        $result->parent = $parent;

        return $result;
    }
}
