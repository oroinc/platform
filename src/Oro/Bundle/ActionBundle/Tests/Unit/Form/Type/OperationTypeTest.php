<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ActionBundle\Form\EventListener\RequiredAttributesListener;
use Oro\Bundle\ActionBundle\Form\Type\OperationType;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeManager;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\PropertyAccess\PropertyPath;

class OperationTypeTest extends FormIntegrationTestCase
{
    /** @var RequiredAttributesListener */
    protected $requiredAttributesListener;

    /** @var OperationType */
    protected $formType;

    protected function setUp()
    {
        $this->requiredAttributesListener = new RequiredAttributesListener();

        $this->formType = new OperationType(
            $this->requiredAttributesListener,
            new ContextAccessor()
        );
        parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->formType, $this->operationManager, $this->requiredAttributesListener);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    OperationType::class => $this->formType
                ],
                []
            ),
        ];
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $defaultData
     * @param array $inputOptions
     * @param array $submittedData
     * @param ActionData $expectedData
     * @param array $expectedChildrenOptions
     * @param ActionData $expectedDefaultData
     */
    public function testSubmit(
        $defaultData,
        array $inputOptions,
        array $submittedData,
        ActionData $expectedData,
        array $expectedChildrenOptions = [],
        ActionData $expectedDefaultData = null
    ) {
        $form = $this->factory->create(OperationType::class, $defaultData, $inputOptions);

        foreach ($expectedChildrenOptions as $name => $options) {
            $this->assertTrue($form->has($name));

            $childFormConfig = $form->get($name)->getConfig();
            foreach ($options as $optionName => $optionValue) {
                $this->assertTrue($childFormConfig->hasOption($optionName));
                $this->assertEquals($optionValue, $childFormConfig->getOption($optionName));
            }
        }

        if ($expectedDefaultData) {
            $this->assertEquals($expectedDefaultData, $form->getData());
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'existing data' => [
                'defaultData' => $this->createOperationData([
                    'field1' => 'data1',
                    'field2' => 'data2',
                    'data' => (object)['property1' => 'data3'],
                ]),
                'inputOptions' => [
                    'operation' => $this->createOperation([
                        'field1' => [],
                        'field2' => [],
                        'field3' => ['property_path' => 'entity.property1'],
                    ]),
                    'attribute_fields' => [
                        'field1'  => [
                            'form_type' => TextType::class,
                            'label' => 'Field1 Label',
                            'options' => ['required' => true]
                        ],
                        'field2' => [
                            'form_type' => TextType::class,
                            'label' => 'Field2 Label Orig'
                        ],
                        'field3' => [
                            'form_type' => TextType::class,
                        ],
                    ],
                ],
                'submittedData' => ['field1' => 'data1', 'field2' => 'data2', 'field3' => 'data3'],
                'expectedData' => $this->createOperationData([
                    'field1' => 'data1',
                    'field2' => 'data2',
                    'data' => (object)['property1' => 'data3'],
                ]),
                'expectedChildrenOptions' => [
                    'field1'  => [
                        'required' => true,
                        'label' => 'Field1 Label'
                    ],
                    'field2' => [
                        'required' => false,
                        'label' => 'Field2 Label Orig'
                    ]
                ]
            ],
            'new data' => [
                'defaultData' => $this->createOperationData(['data' => (object)['property1' => null]]),
                'inputOptions' => [
                    'operation' => $this->createOperation([
                        'field1' => [],
                        'field2' => [],
                        'field3' => ['property_path' => 'entity.property1']
                    ]),
                    'attribute_fields' => [
                        'field1'  => [
                            'form_type' => TextType::class,
                        ],
                        'field2' => [
                            'form_type' => TextType::class,
                        ],
                        'field3' => [
                            'form_type' => TextType::class,
                        ],
                    ],
                ],
                'submittedData' => ['field1' => 'data1', 'field2' => 'data2', 'field3' => 'data3'],
                'expectedData' => $this->createOperationData(
                    [
                        'field1' => 'data1',
                        'field2' => 'data2',
                        'data' => (object)['property1' => 'data3']
                    ],
                    true
                ),
                'expectedChildrenOptions' => [
                    'field1'  => [
                        'required' => false,
                        'label' => 'Field1 Label'
                    ],
                    'field2' => [
                        'required' => false,
                        'label' => 'Field2 Label'
                    ]
                ]
            ],
            'with default values' => [
                'defaultData' => $this->createOperationData(
                    [
                        'default_field1' => 'default_field1_value',
                        'default_field2' => 'default_field2_value'
                    ]
                ),
                'inputOptions' => [
                    'operation' => $this->createOperation([
                        'field1' => [],
                        'field2' => [],
                    ]),
                    'attribute_fields' => [
                        'field1'  => [
                            'form_type' => TextType::class,
                        ],
                        'field2' => [
                            'form_type' => TextType::class,
                        ],
                    ],
                    'attribute_default_values' => [
                        'field1' => new PropertyPath('default_field1'),
                        'field2' => new PropertyPath('default_field2'),
                    ]
                ],
                'submittedData' => [],
                'expectedData' => $this->createOperationData(
                    [
                        'field1' => null,
                        'field2' => null,
                        'default_field1' => 'default_field1_value',
                        'default_field2' => 'default_field2_value'
                    ],
                    true
                ),
                'expectedChildrenOptions' => [
                    'field1'  => [
                        'required' => false,
                        'label' => 'Field1 Label'
                    ],
                    'field2' => [
                        'required' => false,
                        'label' => 'Field2 Label'
                    ]
                ],
                'expectedDefaultData' => $this->createOperationData(
                    [
                        'field1' => 'default_field1_value',
                        'field2' => 'default_field2_value',
                        'default_field1' => 'default_field1_value',
                        'default_field2' => 'default_field2_value',
                    ],
                    true
                )
            ],
        ];
    }

    /**
     * @dataProvider exceptionDataProvider
     *
     * @param array $options
     * @param string $exception
     * @param string $message
     * @param ActionData $data
     */
    public function testException(array $options, $exception, $message, ActionData $data = null)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $this->factory->create(OperationType::class, $data, $options);
    }

    /**
     * @return array
     */
    public function exceptionDataProvider()
    {
        return [
            [
                'options' => [
                    'operation' => $this->createOperation(['field' => []]),
                    'attribute_fields' => [
                        'field'  => [
                            'form_type' => TextType::class,
                        ]
                    ],
                ],
                'exception' => 'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                'message' => 'The required option "data" is missing.',
                'context' => null
            ],
            [
                'options' => [
                    'operation' => $this->createOperation([]),
                    'attribute_fields' => [
                        'field'  => [
                            'form_type' => TextType::class,
                        ]
                    ],
                ],
                'exception' => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'message' => 'Invalid reference to unknown attribute "field" of operation "test_operation".',
                'context' => $this->createOperationData()
            ],
            [
                'options' => [
                    'operation' => $this->createOperation(['field' => []]),
                    'attribute_fields' => [
                        'field' => null
                    ],
                ],
                'exception' => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'message' => 'Parameter "form_type" must be defined for attribute "field" ' .
                    'in operation "test_operation".',
                'context' => $this->createOperationData()
            ]
        ];
    }

    /**
     * @param array $data
     * @param bool $modified
     * @return ActionData
     */
    protected function createOperationData(array $data = [], $modified = false)
    {
        $actionData = new ActionData($data);

        if ($modified) {
            $actionData->setModified(true);
        }

        return $actionData;
    }

    /**
     * @param array $attributes
     * @return Operation|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createOperation(array $attributes = [])
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|AttributeManager $attributeManager */
        $attributeManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\AttributeManager')
            ->disableOriginalConstructor()
            ->getMock();
        $attributeManager->expects($this->any())
            ->method('getAttribute')
            ->willReturnCallback(
                function ($attributeName) use ($attributes) {
                    if (!isset($attributes[$attributeName])) {
                        return null;
                    }

                    $attributeDefinition = $attributes[$attributeName];

                    $attribute = new Attribute();
                    $attribute
                        ->setName($attributeName)
                        ->setLabel(ucfirst($attributeName) . ' Label')
                        ->setType(isset($attributeDefinition['type']) ? $attributeDefinition['type'] : TextType::class)
                        ->setPropertyPath(
                            isset($attributeDefinition['property_path']) ? $attributeDefinition['property_path'] : null
                        );

                    return $attribute;
                }
            );

        /** @var \PHPUnit\Framework\MockObject\MockObject|Operation $operation */
        $operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operation->expects($this->any())
            ->method('getAttributeManager')
            ->with($this->isInstanceOf('Oro\Bundle\ActionBundle\Model\ActionData'))
            ->willReturn($attributeManager);
        $operation->expects($this->any())
            ->method('getName')
            ->willReturn('test_operation');
        $operation->expects($this->any())
            ->method('getDefinition')
            ->willReturn(new OperationDefinition());

        return $operation;
    }
}
