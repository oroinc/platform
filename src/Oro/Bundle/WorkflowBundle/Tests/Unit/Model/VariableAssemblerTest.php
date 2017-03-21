<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Model\AttributeManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\VariableAssembler;
use Oro\Bundle\WorkflowBundle\Model\VariableGuesser;
use Oro\Bundle\WorkflowBundle\Model\VariableManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\AttributeNormalizer;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\WorkflowVariableNormalizer;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;

use Oro\Component\Action\Exception\AssemblerException;

class VariableAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $variableNormalizer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $variableGuesser;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var \Oro\Bundle\WorkflowBundle\Model\Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflow;

    protected function setUp()
    {
        $attributeNormalizer = $this->createMock(AttributeNormalizer::class);
        $serializer = $this->createMock(WorkflowAwareSerializer::class);

        $this->variableNormalizer = $this->createMock(WorkflowVariableNormalizer::class);
        $this->variableNormalizer->addAttributeNormalizer($attributeNormalizer);
        $this->variableNormalizer->setSerializer($serializer);

        $this->variableGuesser = $this->createMock(VariableGuesser::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->createMock(ObjectManager::class));

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
            $this->translator,
            $this->managerRegistry
        );
        $assembler->assemble($this->workflow, $configuration);
    }

    /**
     * @return WorkflowDefinition|\PHPUnit_Framework_MockObject_MockObject
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
                ->method('guessVariableParameters')
                ->with('stdClass', $var['property_path'])
                ->willReturn($guessedParameters);
        }

        $this->variableNormalizer->expects($this->any())
            ->method('denormalizeVariable')
            ->willReturn($expectedVariable->getValue());

        $assembler = new VariableAssembler(
            $this->variableNormalizer,
            $this->variableGuesser,
            $this->translator,
            $this->managerRegistry
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
     * @param array    $options
     */
    public function testEntityIdentifierAssemble($configuration, $expectedVariable, $options = [])
    {
        $configuration = [
            'variable_definitions' => [
                'variables' => $configuration,
            ],
        ];

        $assembler = $this->getMockEntityAssembler($expectedVariable->getValue(), $options);
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
    public function configurationEntityIdentifierProvider()
    {
        $language = new \stdClass();
        $language->code = 'en';

        return [
            'entity_id_field' => [
                [
                    'variable_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'value' => 'en',
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
                    $language,
                    ['class' => 'stdClass', 'identifier' => 'code']
                ),
                [
                    'isIdentifierComposite' => false,
                    'identifier' => ['id'],
                    'isUniqueField' => true
                ]
            ],
        ];
    }

    /**
     * @dataProvider configurationInvalidEntityIdentifierProvider
     *
     * @param array    $configuration
     * @param Variable $expectedVariable
     * @param array    $options
     * @param string  $exception
     * @param string   $message
     */
    public function testInvalidEntityIdentifierAssemble(
        $configuration,
        $expectedVariable,
        $options,
        $exception,
        $message
    ) {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $configuration = [
            'variable_definitions' => [
                'variables' => $configuration,
            ],
        ];

        $assembler = $this->getMockEntityAssembler($expectedVariable->getValue(), $options);
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
    public function configurationInvalidEntityIdentifierProvider()
    {
        $language = new \stdClass();
        $language->code = 'en';

        return [
            'entity_no_manager' => [
                [
                    'variable_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'value' => 'en',
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
                    $language,
                    ['class' => 'stdClass', 'identifier' => 'code']
                ),
                [
                    'getManagerForClass' => false,
                    'isIdentifierComposite' => false,
                    'identifier' => ['id'],
                    'isUniqueField' => true
                ],
                AssemblerException::class,
                'Can\'t get entity manager for class stdClass',
            ],
            'entity_composite_id' => [
                [
                    'variable_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'value' => 'en',
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
                    $language,
                    ['class' => 'stdClass', 'identifier' => 'code']
                ),
                [
                    'isIdentifierComposite' => true,
                    'identifier' => ['id'],
                    'isUniqueField' => true
                ],
                AssemblerException::class,
                'Entity with class stdClass has a composite identifier',
            ],
            'entity_not_unique_id' => [
                [
                    'variable_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'value' => 'en',
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
                    $language,
                    ['class' => 'stdClass', 'identifier' => 'code']
                ),
                [
                    'isIdentifierComposite' => false,
                    'identifier' => ['id'],
                    'isUniqueField' => false
                ],
                AssemblerException::class,
                'Field code is not unique in entity with class stdClass',
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

    /**
     * @param $expectedEntity
     * @param $options
     *
     * @return VariableAssembler|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockEntityAssembler($expectedEntity, $options)
    {
        $classMetadata = $this->createMock(ClassMetadataInfo::class);
        $classMetadata->isIdentifierComposite = $options['isIdentifierComposite'];
        $classMetadata->expects($this->any())
            ->method('getIdentifier')
            ->willReturn($options['identifier']);
        $classMetadata->expects($this->any())
            ->method('isUniqueField')
            ->willReturn($options['isUniqueField']);

        $fakeRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $fakeRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($expectedEntity);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($fakeRepository);

        $this->variableNormalizer->expects($this->any())
            ->method('denormalizeVariable')
            ->willReturn($expectedEntity);

        /** @var VariableAssembler|\PHPUnit_Framework_MockObject_MockObject $assembler */
        $assembler = $this->getMockBuilder(VariableAssembler::class)
            ->setConstructorArgs([
                $this->variableNormalizer,
                $this->variableGuesser,
                $this->translator,
                $this->managerRegistry
            ])
            ->setMethods(['getManagerForClass'])
            ->getMock();

        $getManagerForClass = isset($options['getManagerForClass']) ? $options['getManagerForClass'] : true;
        $managerForClass = $getManagerForClass ? $entityManager : null;

        $assembler->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($managerForClass);

        return $assembler;
    }
}
