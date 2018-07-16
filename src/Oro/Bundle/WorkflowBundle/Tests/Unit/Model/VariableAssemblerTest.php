<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\AttributeManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\VariableAssembler;
use Oro\Bundle\WorkflowBundle\Model\VariableGuesser;
use Oro\Bundle\WorkflowBundle\Model\VariableManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\AttributeNormalizer;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\WorkflowVariableNormalizer;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;
use Oro\Component\Action\Exception\AssemblerException;
use Symfony\Component\Translation\TranslatorInterface;

class VariableAssemblerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WorkflowVariableNormalizer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $variableNormalizer;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $variableGuesser;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var \Oro\Bundle\WorkflowBundle\Model\Workflow|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $workflow;

    protected function setUp()
    {
        $attributeNormalizer = $this->createMock(AttributeNormalizer::class);
        $serializer = $this->createMock(WorkflowAwareSerializer::class);

        $this->variableNormalizer = $this->getMockBuilder(WorkflowVariableNormalizer::class)
            ->setConstructorArgs([$this->createMock(ManagerRegistry::class)])
            ->setMethods(['denormalizeVariable'])
            ->getMock();
        $this->variableNormalizer->addAttributeNormalizer($attributeNormalizer);
        $this->variableNormalizer->setSerializer($serializer);

        $this->variableGuesser = $this->createMock(VariableGuesser::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $aclManager = $this->createMock(AclManager::class);
        $restrictionManager = $this->createMock(RestrictionManager::class);
        $attributeManager = $this->createMock(AttributeManager::class);
        $variableManager = $this->createMock(VariableManager::class);

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->setMethods(['getName', 'getVariables', 'getDefinition'])
            ->setConstructorArgs([
                $doctrineHelper,
                $aclManager,
                $restrictionManager,
                null,
                $attributeManager,
                null,
                $variableManager,
            ])->getMock();

        $this->workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($this->getWorkflowDefinition());
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     *
     * @param array  $configuration
     * @param string $exception
     * @param string $message
     */
    public function testAssembleRequiredOptionException($configuration, $exception, $message)
    {
        $this->expectException($exception);

        if (false === @preg_match($message, $exception)) {
            $this->expectExceptionMessage($message);
        } else {
            $this->expectExceptionMessageRegExp($message);
        }

        $configuration = [
            'variable_definitions' => [
                'variables' => $configuration,
            ],
        ];

        $assembler = new VariableAssembler(
            $this->variableNormalizer,
            $this->variableGuesser,
            $this->translator
        );
        $assembler->assemble($this->workflow, $configuration);
    }

    /**
     * @return WorkflowDefinition|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getWorkflowDefinition()
    {
        return $this->createMock(WorkflowDefinition::class);
    }

    /**
     * @return array
     */
    public function invalidOptionsDataProvider()
    {
        return [
            'no_options' => [
                ['name' => []],
                AssemblerException::class,
                'Option "type" is required',
            ],
            'no_type' => [
                ['name' => ['label' => 'test', 'value' => 'test']],
                AssemblerException::class,
                'Option "type" is required',
            ],
            'invalid_type' => [
                ['name' => ['label' => 'Label', 'type' => 'text', 'value' => 'text']],
                AssemblerException::class,
                'Invalid variable type "text", allowed types are "bool", "boolean", "int", "integer", ' .
                '"float", "string", "array", "object"',
            ],
            'invalid_type_class' => [
                ['name' => ['label' => 'Label', 'type' => 'string', 'options' => ['class' => 'stdClass']]],
                AssemblerException::class,
                'Option "class" cannot be used in variable "name"',
            ],
            'missing_object_class' => [
                ['name' => ['label' => 'Label', 'type' => 'object']],
                AssemblerException::class,
                'Option "class" is required in variable "name"',
            ],
            'missing_entity_class' => [
                ['name' => ['label' => 'Label', 'type' => 'entity']],
                AssemblerException::class,
                'Option "class" is required in variable "name"',
            ],
            'invalid_class' => [
                ['name' => ['label' => 'Label', 'type' => 'object', 'options' => ['class' => 'InvalidClass']]],
                AssemblerException::class,
                'Class "InvalidClass" referenced by "class" option in variable "name" not found',
            ],
            'not_allowed_entity_acl' => [
                [
                    'name' => [
                        'label' => 'Label',
                        'type' => 'object',
                        'options' => [
                            'class' => 'stdClass'
                        ],
                        'entity_acl' => [
                            'update' => false
                        ],
                    ]
                ],
                AssemblerException::class,
                'Variable "Label" with type "object" can\'t have entity ACL'
            ],
        ];
    }

    /**
     * @dataProvider configurationDataProvider
     *
     * @param array    $configuration
     * @param Variable $expectedVariable
     * @param array    $guessedParameters
     */
    public function testAssemble($configuration, $expectedVariable, array $guessedParameters = [])
    {
        $variableConfig = $configuration;
        $configuration = [
            'variable_definitions' => [
                'variables' => $configuration,
            ],
        ];

        if ($guessedParameters && array_key_exists('property_path', array_values($variableConfig)[0])) {
            $var = array_values($variableConfig)[0];
            $this->variableGuesser->expects($this->any())
                ->method('guessParameters')
                ->with('stdClass', $var['property_path'])
                ->willReturn($guessedParameters);
        }

        $this->variableNormalizer->expects($this->any())
            ->method('denormalizeVariable')
            ->willReturn($expectedVariable->getValue());

        $assembler = new VariableAssembler(
            $this->variableNormalizer,
            $this->variableGuesser,
            $this->translator
        );
        $variables = $assembler->assemble($this->workflow, $configuration);

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $variables);
        $this->assertCount(1, $variables);
        $this->assertTrue($variables->containsKey($expectedVariable->getName()));
        $this->assertEquals($expectedVariable, $variables->get($expectedVariable->getName()));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function configurationDataProvider()
    {
        return [
            'string' => [
                ['variable_one' => ['label' => 'label', 'type' => 'string', 'value' => 'text']],
                $this->getVariable('variable_one', 'label', 'string', 'text'),
            ],
            'bool' => [
                ['variable_one' => ['label' => 'label', 'type' => 'bool', 'value' => true]],
                $this->getVariable('variable_one', 'label', 'bool', true),
            ],
            'boolean' => [
                ['variable_one' => ['label' => 'label', 'type' => 'boolean', 'value' => true]],
                $this->getVariable('variable_one', 'label', 'boolean', true),
            ],
            'int' => [
                ['variable_one' => ['label' => 'label', 'type' => 'int', 'value' => 1]],
                $this->getVariable('variable_one', 'label', 'int', 1),
            ],
            'integer' => [
                ['variable_one' => ['label' => 'label', 'type' => 'integer', 'value' => 1]],
                $this->getVariable('variable_one', 'label', 'integer', 1),
            ],
            'float' => [
                ['variable_one' => ['label' => 'label', 'type' => 'float', 'value' => 2.1]],
                $this->getVariable('variable_one', 'label', 'float', 2.1),
            ],
            'array' => [
                ['variable_one' => ['label' => 'label', 'type' => 'array', 'value' => ['text', true, 1, 2.1]]],
                $this->getVariable('variable_one', 'label', 'array', ['text', true, 1, 2.1]),
            ],
            'object' => [
                [
                    'variable_one' => [
                        'label' => 'label',
                        'type' => 'object',
                        'value' => '2017-03-15 00:00:00',
                        'options' => ['class' => 'DateTime'],
                    ],
                ],
                $this->getVariable(
                    'variable_one',
                    'label',
                    'object',
                    new \DateTime('2017-03-15 00:00:00'),
                    ['class' => 'DateTime']
                ),
            ],
            'entity_minimal' => [
                [
                    'variable_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],
                    ]
                ],
                $this->getVariable('variable_one', 'label', 'entity', null, ['class' => 'stdClass'])
            ],
            'with_related_entity' => [
                [
                    'entity' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],

                    ]
                ],
                $this->getVariable('entity', 'label', 'entity', null, ['class' => 'stdClass'])
            ],
            'with_entity_acl' => [
                [
                    'variable_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],
                        'entity_acl' => ['update' => false],
                    ]
                ],
                $this->getVariable(
                    'variable_one',
                    'label',
                    'entity',
                    null,
                    ['class' => 'stdClass'],
                    null,
                    ['update' => false]
                )
            ],
            'entity_full_guessed_parameters' => [
                [
                    'variable_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],
                        'property_path' => 'entity.field'
                    ]
                ],
                $this->getVariable('variable_one', 'label', 'entity', null, ['class' => 'stdClass'], 'entity.field'),
                'guessedParameters' => [
                    'label' => 'guessed label',
                    'type' => 'object',
                    'options' => ['class' => 'GuessedClass'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider configurationEntityIdentifierProvider
     *
     * @param array    $configuration
     * @param Variable $expectedVariable
     */
    public function testEntityIdentifierAssemble($configuration, $expectedVariable)
    {
        $configuration = [
            'variable_definitions' => [
                'variables' => $configuration,
            ],
        ];

        $this->variableNormalizer->expects($this->any())
            ->method('denormalizeVariable')
            ->willReturn($expectedVariable->getValue());

        $assembler = new VariableAssembler(
            $this->variableNormalizer,
            $this->variableGuesser,
            $this->translator
        );
        $variables = $assembler->assemble($this->workflow, $configuration);

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $variables);
        $this->assertCount(1, $variables);
        $this->assertTrue($variables->containsKey($expectedVariable->getName()));
        $this->assertEquals($expectedVariable, $variables->get($expectedVariable->getName()));
        $this->assertEquals($expectedVariable->getValue(), $variables->get($expectedVariable->getName())->getValue());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function configurationEntityIdentifierProvider()
    {
        return [
            'entity_id_field' => [
                [
                    'variable_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'value' => 1,
                        'options' => [
                            'class' => 'stdClass',
                            'identifier' => 'code'
                        ],
                    ]
                ],
                $this->getVariable(
                    'variable_one',
                    'label',
                    'entity',
                    (object) ['code' => 1],
                    ['class' => 'stdClass', 'identifier' => 'code']
                )
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $type
     * @param mixed  $value
     * @param array  $options
     * @param string $propertyPath
     * @param array  $entityAcl
     *
     * @return Variable
     */
    protected function getVariable(
        $name,
        $label,
        $type,
        $value,
        array $options = [],
        $propertyPath = null,
        array $entityAcl = []
    ) {
        $variable = new Variable();
        $variable
            ->setName($name)
            ->setLabel($label)
            ->setType($type)
            ->setValue($value)
            ->setOptions($options)
            ->setPropertyPath($propertyPath)
            ->setEntityAcl($entityAcl);

        return $variable;
    }
}
