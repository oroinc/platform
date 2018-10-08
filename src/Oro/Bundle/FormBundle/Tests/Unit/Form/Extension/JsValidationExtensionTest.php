<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Extension;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintsProviderInterface;
use Oro\Bundle\FormBundle\Form\Extension\JsValidationExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints;

class JsValidationExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $constraintsProvider;

    /**
     * @var JsValidationExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->constraintsProvider = $this->createMock(ConstraintsProviderInterface::class);
        $this->extension = new JsValidationExtension($this->constraintsProvider);
    }

    /**
     * @dataProvider finishViewAddOptionalGroupAttributeDataProvider
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     * @param array $expectedAttributes
     */
    public function testFinishViewAddOptionalGroupAttribute(
        FormView $view,
        FormInterface $form,
        array $options,
        array $expectedAttributes
    ) {
        $this->constraintsProvider->expects($this->once())
            ->method('getFormConstraints')
            ->will($this->returnValue([]));

        $this->extension->finishView($view, $form, $options);

        $this->assertEquals($expectedAttributes, $view->vars['attr']);
    }

    public function finishViewAddOptionalGroupAttributeDataProvider()
    {
        return [
            'not_optional_group_without_children' => [
                'view' => $this->createView(
                    [],
                    [],
                    $this->createView()
                ),
                'form' => $this->createForm(),
                'options' => [],
                'expectedAttributes' => []
            ],
            'not_optional_group_without_parent' => [
                'view' => $this->createView(
                    [],
                    [$this->createView()]
                ),
                'form' => $this->createForm(),
                'options' => [],
                'expectedAttributes' => []
            ],
            'not_optional_group_with_choices' => [
                'view' => $this->createView(
                    [],
                    [$this->createView()],
                    $this->createView()
                ),
                'form' => $this->createForm(),
                'options' => [
                    'choices' => ['1' => 'Yes', '0' => 'No']
                ],
                'expectedAttributes' => []
            ],
            'not_optional_group_required' => [
                'view' => $this->createView(
                    [],
                    [$this->createView()],
                    $this->createView()
                ),
                'form' => $this->createForm(),
                'options' => [
                    'required' => true
                ],
                'expectedAttributes' => []
            ],
            'not_optional_group_required_and_not_inherit_data' => [
                'view' => $this->createView(
                    [],
                    [$this->createView()],
                    $this->createView()
                ),
                'form' => $this->createForm(),
                'options' => [
                    'required' => true,
                    'inherit_data' => false
                ],
                'expectedAttributes' => []
            ],
            'optional_group' => [
                'view' => $this->createView(
                    [],
                    [$this->createView()],
                    $this->createView()
                ),
                'form' => $this->createForm(),
                'options' => [
                    'required' => false
                ],
                'expectedAttributes' => [
                    'data-validation-optional-group' => null,
                ]
            ],
            'optional_group_required_but_inherit_data' => [
                'view' => $this->createView(
                    [],
                    [$this->createView()],
                    $this->createView()
                ),
                'form' => $this->createForm(),
                'options' => [
                    'required' => true,
                    'inherit_data' => true
                ],
                'expectedAttributes' => [
                    'data-validation-optional-group' => null,
                ]
            ],
        ];
    }

    /**
     * @dataProvider finishViewAddDataValidationAttributeDataProvider
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $expectedConstraints
     * @param array $expectedAttributes
     */
    public function testFinishViewAddDataValidationAttribute(
        FormView $view,
        FormInterface $form,
        array $expectedConstraints,
        array $expectedAttributes
    ) {
        $this->constraintsProvider->expects($this->once())
            ->method('getFormConstraints')
            ->will($this->returnValue($expectedConstraints));

        $this->extension->finishView($view, $form, []);

        $this->assertEquals($expectedAttributes, $view->vars['attr']);
    }

    /**
     * @SuppressWarnings(PHPMD)
     *
     * @return array
     */
    public function finishViewAddDataValidationAttributeDataProvider()
    {
        $constraintWithNestedData = new Constraints\NotNull();
        $constraintWithNestedData->message = [
            'object' => new \stdClass(),
            'array' => [
                'object' => new \stdClass(),
                'integer' => 2,
            ],
            'integer' => 1,
        ];

        $constraintWithCustomName = $this->createMock('Symfony\Component\Validator\Constraint');
        $constraintWithCustomName->foo = 1;

        return [
            'set_nested_data' => [
                'view' => $this->createView(),
                'form' => $this->createForm(),
                'expectedConstraints' => [$constraintWithNestedData],
                'expectedAttributes' => [
                    'data-validation' => '{"NotNull":{"message":{"array":{"integer":2},"integer":1},"payload":null}}'
                ]
            ],
            'set_custom_name' => [
                'view' => $this->createView(),
                'form' => $this->createForm(),
                'expectedConstraints' => [$constraintWithCustomName],
                'expectedAttributes' => [
                    'data-validation' => '{"' . get_class($constraintWithCustomName) . '":{"payload":null}}'
                ]
            ],
            'set_default' => [
                'view' => $this->createView(),
                'form' => $this->createForm(),
                'expectedConstraints' => [new Constraints\NotBlank()],
                'expectedAttributes' => [
                    'data-required'   => 1,
                    'data-validation' => '{"NotBlank":{"message":"This value should not be blank.","payload":null}}'
                ]
            ],
            'set_similar_constrains' => [
                'view' => $this->createView(
                    [
                        'attr' => [
                            'data-validation' => '{"NotNull":{"message":"This value should not be null."}}'
                        ]
                    ]
                ),
                'form' => $this->createForm(),
                'expectedConstraints' => [
                    new Constraints\Regex([
                        'pattern' => "/^[a-z]+[a-z]*$/i",
                        'message' => "Value should start with a symbol and contain only alphabetic symbols"
                    ]),
                    new Constraints\Regex([
                        'pattern' => "/^id$/i",
                        'match' => false,
                        'message' => "Value cannot be used as a field name."
                    ]),
                ],
                'expectedAttributes' => [
                    'data-validation' =>
                        '{' .
                        '"NotNull":{"message":"This value should not be null."},' .
                        '"Regex":[{"message":"Value should start with a symbol and contain only alphabetic symbols",' .
                        '"pattern":"\/^[a-z]+[a-z]*$\/i","htmlPattern":null,"match":true,"payload":null},' .
                        '{"message":"Value cannot be used as a field name.",' .
                        '"pattern":"\/^id$\/i","htmlPattern":null,"match":false,"payload":null}' .
                        ']}',
                ]
            ],
            'merge_with_array' => [
                'view' => $this->createView(
                    [
                        'attr' => [
                            'data-validation' => [
                                'NotNull' => ['NotNull' => ['message' => 'This value should not be null.']]
                            ]
                        ]
                    ]
                ),
                'form' => $this->createForm(),
                'expectedConstraints' => [new Constraints\NotBlank()],
                'expectedAttributes' => [
                    'data-validation' =>
                        '{' .
                        '"NotNull":{"NotNull":{"message":"This value should not be null."}},' .
                        '"NotBlank":{"message":"This value should not be blank.","payload":null}' .
                        '}',
                    'data-required'   => 1
                ]
            ],
            'merge_with_json_string' => [
                'view' => $this->createView(
                    [
                        'attr' => [
                            'data-validation' => '{"NotNull":{"message":"This value should not be null."}}'
                        ]
                    ]
                ),
                'form' => $this->createForm(),
                'expectedConstraints' => [new Constraints\NotBlank()],
                'expectedAttributes' => [
                    'data-validation' =>
                        '{' .
                        '"NotNull":{"message":"This value should not be null."},' .
                        '"NotBlank":{"message":"This value should not be blank.","payload":null}' .
                        '}',
                    'data-required'   => 1
                ]
            ],
            'override_invalid_value' => [
                'view' => $this->createView(
                    [
                        'attr' => [
                            'data-validation' => '{"NotNull":}',
                        ]
                    ]
                ),
                'form' => $this->createForm(),
                'expectedConstraints' => [
                    new Constraints\NotBlank()
                ],
                'expectedAttributes' => [
                    'data-validation' =>
                        '{"NotBlank":{"message":"This value should not be blank.","payload":null}}',
                    'data-required'   => 1
                ]
            ],
        ];
    }

    /**
     * @param array $vars
     * @param array $children
     * @param FormView $parent
     * @return FormView
     */
    protected function createView(array $vars = [], array $children = [], FormView $parent = null)
    {
        $result = new FormView();
        $result->vars = array_merge_recursive($result->vars, $vars);
        $result->children = $children;
        $result->parent = $parent;
        return $result;
    }

    /**
     * @return FormInterface
     */
    protected function createForm()
    {
        return $this->createMock('Symfony\Component\Form\Test\FormInterface');
    }
}
