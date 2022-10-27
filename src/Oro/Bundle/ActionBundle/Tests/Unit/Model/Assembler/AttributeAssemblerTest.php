<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Assembler;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Exception\AssemblerException;
use Oro\Bundle\ActionBundle\Exception\MissedRequiredOptionException;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeGuesser;

class AttributeAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ActionData|\PHPUnit\Framework\MockObject\MockObject */
    private $actionData;

    /** @var AttributeGuesser|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeGuesser;

    /** @var AttributeAssembler */
    private $assembler;

    protected function setUp(): void
    {
        $this->actionData = $this->createMock(ActionData::class);
        $this->attributeGuesser = $this->createMock(AttributeGuesser::class);

        $this->actionData->expects($this->any())
            ->method('getEntity')
            ->willReturn(new \stdClass());

        $this->assembler = new AttributeAssembler($this->attributeGuesser);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testAssembleRequiredOptionException(array $configuration, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $this->assembler->assemble($this->actionData, $configuration);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidOptionsDataProvider(): array
    {
        return [
            'no_options' => [
                [
                    'attribute_name' => [
                        'property_path' => null,
                    ],
                ],
                MissedRequiredOptionException::class,
                'Option "label" is required',
            ],
            'no_type' => [
                [
                    'attribute_name' => [
                        'label' => 'test',
                        'property_path' => null,
                    ],
                ],
                MissedRequiredOptionException::class,
                'Option "type" is required',
            ],
            'no_label' => [
                [
                    'attribute_name' => [
                        'type' => 'test',
                        'property_path' => null,
                    ],
                ],
                MissedRequiredOptionException::class,
                'Option "label" is required',
            ],
            'invalid_type' => [
                [
                    'attribute_name' => [
                        'label' => 'Label',
                        'type' => 'text',
                        'property_path' => null,
                    ],
                ],
                AssemblerException::class,
                'Invalid attribute type "text", allowed types are "bool", "boolean", "int", "integer", ' .
                '"float", "string", "array", "object", "entity"',
            ],
            'invalid_type_class' => [
                [
                    'attribute_name' => [
                        'label' => 'Label',
                        'type' => 'string',
                        'options' => [
                            'class' => 'stdClass',
                        ],
                        'property_path' => null,
                    ],
                ],
                AssemblerException::class,
                'Option "class" cannot be used in attribute "attribute_name"',
            ],
            'missing_object_class' => [
                [
                    'attribute_name' => [
                        'label' => 'Label',
                        'type' => 'object',
                        'property_path' => null,
                    ],
                ],
                MissedRequiredOptionException::class,
                'Option "class" is required in attribute "attribute_name"',
            ],
            'missing_entity_class' => [
                [
                    'attribute_name' => [
                        'label' => 'Label',
                        'type' => 'entity',
                        'property_path' => null,
                    ],
                ],
                MissedRequiredOptionException::class,
                'Option "class" is required in attribute "attribute_name"',
            ],
            'invalid_class' => [
                [
                    'attribute_name' => [
                        'label' => 'Label',
                        'type' => 'object',
                        'options' => [
                            'class' => 'InvalidClass',
                        ],
                        'property_path' => null,
                    ],
                ],
                AssemblerException::class,
                'Class "InvalidClass" referenced by "class" option in attribute "attribute_name" not found',
            ],
            'not_allowed_entity_acl' => [
                [
                    'attribute_name' => [
                        'label' => 'Label',
                        'type' => 'object',
                        'options' => [
                            'class' => 'stdClass',
                        ],
                        'entity_acl' => [
                            'update' => false,
                        ],
                    ],
                ],
                AssemblerException::class,
                'Attribute "Label" with type "object" can\'t have entity ACL',
            ],
        ];
    }

    /**
     * @dataProvider configurationDataProvider
     */
    public function testAssemble(
        array $configuration,
        Attribute $expectedAttribute,
        array $guessedParameters = []
    ): void {
        $attributeConfiguration = current($configuration);
        if ($guessedParameters && array_key_exists('property_path', $attributeConfiguration)) {
            $this->attributeGuesser->expects($this->any())
                ->method('guessParameters')
                ->with('stdClass', $attributeConfiguration['property_path'])
                ->willReturn($guessedParameters);
        }

        $attributes = $this->assembler->assemble($this->actionData, $configuration);

        $this->assertInstanceOf(ArrayCollection::class, $attributes);
        $this->assertCount(array_key_exists('entity', $configuration) ? 1 : 2, $attributes);
        $this->assertTrue($attributes->containsKey($expectedAttribute->getName()));
        $this->assertEquals($expectedAttribute, $attributes->get($expectedAttribute->getName()));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function configurationDataProvider(): array
    {
        return [
            'string' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'string',
                    ],
                ],
                $this->getAttribute('attribute_one', 'label', 'string'),
            ],
            'bool' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'bool',
                    ],
                ],
                $this->getAttribute('attribute_one', 'label', 'bool'),
            ],
            'boolean' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'boolean',
                    ],
                ],
                $this->getAttribute('attribute_one', 'label', 'boolean'),
            ],
            'int' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'int',
                    ],
                ],
                $this->getAttribute('attribute_one', 'label', 'int'),
            ],
            'integer' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'integer',
                    ],
                ],
                $this->getAttribute('attribute_one', 'label', 'integer'),
            ],
            'float' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'float',
                    ],
                ],
                $this->getAttribute('attribute_one', 'label', 'float'),
            ],
            'array' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'array',
                    ],
                ],
                $this->getAttribute('attribute_one', 'label', 'array'),
            ],
            'object' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'object',
                        'options' => ['class' => 'stdClass'],
                    ],
                ],
                $this->getAttribute('attribute_one', 'label', 'object', ['class' => 'stdClass']),
            ],
            'entity_minimal' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],
                    ],
                ],
                $this->getAttribute('attribute_one', 'label', 'entity', ['class' => 'stdClass']),
            ],
            'with_default' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'string',
                        'options' => [],
                        'default' => 'sample_value',
                    ],
                ],
                $this->getAttribute('attribute_one', 'label', 'string', [], 'sample_value'),
            ],
            'with_related_entity' => [
                [
                    'entity' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],

                    ],
                ],
                $this->getAttribute('entity', 'label', 'entity', ['class' => 'stdClass']),
            ],
            'with_entity_acl' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],
                        'entity_acl' => ['update' => false],
                    ],
                ],
                $this->getAttribute(
                    'attribute_one',
                    'label',
                    'entity',
                    ['class' => 'stdClass'],
                    null,
                    null,
                    ['update' => false]
                ),
            ],
            'entity_minimal_guessed_parameters' => [
                [
                    'attribute_one' => [
                        'property_path' => 'entity.field',
                    ],
                ],
                $this->getAttribute('attribute_one', 'label', 'entity', ['class' => 'stdClass'], null, 'entity.field'),
                'guessedParameters' => [
                    'label' => 'label',
                    'type' => 'entity',
                    'options' => ['class' => 'stdClass'],
                ],
            ],
            'entity_full_guessed_parameters' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],
                        'property_path' => 'entity.field',
                    ],
                ],
                $this->getAttribute('attribute_one', 'label', 'entity', ['class' => 'stdClass'], null, 'entity.field'),
                'guessedParameters' => [
                    'label' => 'guessed label',
                    'type' => 'object',
                    'options' => ['class' => 'GuessedClass'],
                ],
            ],
        ];
    }

    private function getAttribute(
        $name,
        string $label,
        string $type,
        array $options = [],
        mixed $default = null,
        ?string $propertyPath = null,
        array $entityAcl = []
    ): Attribute {
        $attribute = new Attribute();
        $attribute->setName($name);
        $attribute->setLabel($label);
        $attribute->setType($type);
        $attribute->setOptions($options);
        $attribute->setDefault($default);
        $attribute->setPropertyPath($propertyPath);
        $attribute->setEntityAcl($entityAcl);

        return $attribute;
    }
}
