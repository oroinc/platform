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
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\PropertyAccess\PropertyPath;

class OperationTypeTest extends FormIntegrationTestCase
{
    /** @var OperationType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new OperationType(
            new RequiredAttributesListener(),
            new ContextAccessor()
        );
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        mixed $defaultData,
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
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider(): array
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
     */
    public function testException(array $options, string $exception, string $message, ActionData $data = null)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $this->factory->create(OperationType::class, $data, $options);
    }

    public function exceptionDataProvider(): array
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
                'exception' => MissingOptionsException::class,
                'message' => 'The required option "data" is missing.',
                'context' => null
            ],
            [
                'options' => [
                    'operation' => $this->createOperation(),
                    'attribute_fields' => [
                        'field'  => [
                            'form_type' => TextType::class,
                        ]
                    ],
                ],
                'exception' => InvalidConfigurationException::class,
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
                'exception' => InvalidConfigurationException::class,
                'message' => 'Parameter "form_type" must be defined for attribute "field" ' .
                    'in operation "test_operation".',
                'context' => $this->createOperationData()
            ]
        ];
    }

    private function createOperationData(array $data = [], bool $modified = false): ActionData
    {
        $actionData = new ActionData($data);
        if ($modified) {
            $actionData->setModified(true);
        }

        return $actionData;
    }

    private function createOperation(array $attributes = []): Operation
    {
        $attributeManager = $this->createMock(AttributeManager::class);
        $attributeManager->expects($this->any())
            ->method('getAttribute')
            ->willReturnCallback(function ($attributeName) use ($attributes) {
                if (!isset($attributes[$attributeName])) {
                    return null;
                }

                $attributeDefinition = $attributes[$attributeName];

                $attribute = new Attribute();
                $attribute
                    ->setName($attributeName)
                    ->setLabel(ucfirst($attributeName) . ' Label')
                    ->setType($attributeDefinition['type'] ?? TextType::class)
                    ->setPropertyPath($attributeDefinition['property_path'] ?? null);

                return $attribute;
            });

        $operation = $this->createMock(Operation::class);
        $operation->expects($this->any())
            ->method('getAttributeManager')
            ->with($this->isInstanceOf(ActionData::class))
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
