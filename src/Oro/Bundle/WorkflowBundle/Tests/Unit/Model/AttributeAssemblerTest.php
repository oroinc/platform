<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeGuesser;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\AttributeAssembler;
use Oro\Component\Action\Exception\AssemblerException;
use Symfony\Contracts\Translation\TranslatorInterface;

class AttributeAssemblerTest extends \PHPUnit\Framework\TestCase
{
    private TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(static fn (string $key) => sprintf('[trans]%s[/trans]', $key));
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testAssembleRequiredOptionException(array $configuration, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $assembler = new AttributeAssembler($this->createMock(AttributeGuesser::class), $this->translator);
        $definition = $this->getWorkflowDefinition();
        $assembler->assemble($definition, $configuration);
    }

    /**
     * @return WorkflowDefinition|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getWorkflowDefinition()
    {
        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())
            ->method('getLabel')
            ->willReturn('test_workflow_label');

        return $definition;
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            'no_options' => [
                ['name' => ['property_path' => null]],
                AssemblerException::class,
                'Option "label" is required',
            ],
            'no_type' => [
                ['name' => ['label' => 'test', 'property_path' => null]],
                AssemblerException::class,
                'Option "type" is required',
            ],
            'no_label' => [
                ['name' => ['type' => 'test', 'property_path' => null]],
                AssemblerException::class,
                'Option "label" is required',
            ],
            'invalid_type' => [
                ['name' => ['label' => 'Label', 'type' => 'text', 'property_path' => null]],
                AssemblerException::class,
                'Invalid attribute type "text", allowed types are "bool", "boolean", "int", "integer", ' .
                '"float", "string", "array", "object", "entity"',
            ],
            'can_not_guess_type' => [
                ['test_name' => ['property_path' => 'test.property']],
                AssemblerException::class,
                'Workflow "[trans]test_workflow_label[/trans]": Option "type" for attribute "test_name" ' .
                'with property path "test.property" can not be guessed',
            ],
            'invalid_type_class' => [
                [
                    'name' => [
                        'label' => 'Label',
                        'type' => 'string',
                        'options' => ['class' => 'stdClass'],
                        'property_path' => null,
                    ],
                ],
                AssemblerException::class,
                'Option "class" cannot be used in attribute "name"',
            ],
            'missing_object_class' => [
                ['name' => ['label' => 'Label', 'type' => 'object', 'property_path' => null]],
                AssemblerException::class,
                'Option "class" is required in attribute "name"',
            ],
            'missing_entity_class' => [
                ['name' => ['label' => 'Label', 'type' => 'entity', 'property_path' => null]],
                AssemblerException::class,
                'Option "class" is required in attribute "name"',
            ],
            'invalid_class' => [
                [
                    'name' => [
                        'label' => 'Label',
                        'type' => 'object',
                        'options' => ['class' => 'InvalidClass'],
                        'property_path' => null,
                    ],
                ],
                AssemblerException::class,
                'Class "InvalidClass" referenced by "class" option in attribute "name" not found',
            ],
            'not_allowed_entity_acl' => [
                [
                    'name' => [
                        'label' => 'Label',
                        'type' => 'object',
                        'options' => ['class' => 'stdClass'],
                        'entity_acl' => ['update' => false],
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
        array $guessedParameters = [],
        array $transitionConfigurations = []
    ): void {
        $relatedEntity = \stdClass::class;

        $attributeGuesser = $this->createMock(AttributeGuesser::class);
        $attributeConfiguration = current($configuration);
        if ($guessedParameters && array_key_exists('property_path', $attributeConfiguration)) {
            $attributeGuesser->expects($this->any())
                ->method('guessParameters')
                ->with($relatedEntity, $attributeConfiguration['property_path'])
                ->willReturn($guessedParameters);
        }

        $assembler = new AttributeAssembler($attributeGuesser, $this->translator);
        $definition = $this->getWorkflowDefinition();
        $definition->expects($this->once())
            ->method('getEntityAttributeName')
            ->willReturn('entity_attribute');
        $definition->expects($this->any())
            ->method('getRelatedEntity')
            ->willReturn($relatedEntity);

        $expectedAttributesCount = count($configuration) + count($transitionConfigurations);
        if (!array_key_exists('entity_attribute', $configuration)) {
            $expectedAttributesCount++;
        }

        $attributes = $assembler->assemble($definition, $configuration, $transitionConfigurations);
        $this->assertInstanceOf(ArrayCollection::class, $attributes);
        $this->assertCount($expectedAttributesCount, $attributes);
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
                    'attribute_one' => ['label' => 'label', 'type' => 'string', 'property_path' => null],
                ],
                $this->getAttribute('attribute_one', 'label', 'string'),
            ],
            'bool' => [
                ['attribute_one' => ['label' => 'label', 'type' => 'bool', 'property_path' => null]],
                $this->getAttribute('attribute_one', 'label', 'bool'),
            ],
            'boolean' => [
                ['attribute_one' => ['label' => 'label', 'type' => 'boolean', 'property_path' => null]],
                $this->getAttribute('attribute_one', 'label', 'boolean'),
            ],
            'int' => [
                ['attribute_one' => ['label' => 'label', 'type' => 'int', 'property_path' => null]],
                $this->getAttribute('attribute_one', 'label', 'int'),
            ],
            'integer' => [
                ['attribute_one' => ['label' => 'label', 'type' => 'integer', 'property_path' => null]],
                $this->getAttribute('attribute_one', 'label', 'integer'),
            ],
            'float' => [
                ['attribute_one' => ['label' => 'label', 'type' => 'float', 'property_path' => null]],
                $this->getAttribute('attribute_one', 'label', 'float'),
            ],
            'array' => [
                [
                    'attribute_one' => ['label' => 'label', 'type' => 'array', 'property_path' => null],
                ],
                $this->getAttribute('attribute_one', 'label', 'array'),
            ],
            'object' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'object',
                        'options' => ['class' => 'stdClass'],
                        'property_path' => null,
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
                        'property_path' => null,
                    ],
                ],
                $this->getAttribute(
                    'attribute_one',
                    'label',
                    'entity',
                    ['class' => 'stdClass']
                ),
            ],
            'with_related_entity' => [
                [
                    'entity_attribute' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],
                        'property_path' => null,
                    ],
                ],
                $this->getAttribute(
                    'entity_attribute',
                    'label',
                    'entity',
                    ['class' => 'stdClass']
                ),
            ],
            'with_default' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'string',
                        'options' => [],
                        'default' => 'sample_value',
                        'property_path' => null,
                    ],
                ],
                $this->getAttribute(
                    'attribute_one',
                    'label',
                    'string',
                    [],
                    'sample_value',
                    null,
                    []
                ),
            ],
            'with_entity_acl' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],
                        'property_path' => null,
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
                $this->getAttribute(
                    'attribute_one',
                    'label',
                    'entity',
                    ['class' => 'stdClass'],
                    null,
                    'entity.field'
                ),
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
                $this->getAttribute(
                    'attribute_one',
                    'label',
                    'entity',
                    ['class' => 'stdClass'],
                    null,
                    'entity.field'
                ),
                'guessedParameters' => [
                    'label' => 'guessed label',
                    'type' => 'object',
                    'options' => ['class' => 'GuessedClass'],
                ],
            ],
            'add_init_context_attribute' => [
                [],
                $this->getAttribute(
                    'attribute_one',
                    'attribute_one',
                    'object',
                    ['class' => ButtonSearchContext::class]
                ),
                [],
                [
                    'transition1' => ['init_context_attribute' => 'attribute_one'],
                    'transition2' => ['init_context_attribute' => 'source'],
                ],
            ],
        ];
    }

    private function getAttribute(
        string $name,
        string $label,
        string $type,
        array $options = [],
        mixed $default = null,
        string $propertyPath = null,
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
