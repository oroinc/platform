<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\Normalizer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActionBundle\Model\AttributeManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\SerializerException;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\VariableManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\AttributeNormalizer;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\WorkflowVariableNormalizer;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowAwareSerializer;

class WorkflowVariableNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttributeNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeNormalizer;

    /** @var WorkflowAwareSerializer|\PHPUnit\Framework\MockObject\MockObject */
    private $serializer;

    /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject */
    private $workflow;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeManager;

    /** @var WorkflowVariableNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->attributeNormalizer = $this->createMock(AttributeNormalizer::class);
        $this->serializer = $this->createMock(WorkflowAwareSerializer::class);
        $this->attributeManager = $this->createMock(AttributeManager::class);
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $aclManager = $this->createMock(AclManager::class);
        $restrictionManager = $this->createMock(RestrictionManager::class);
        $variableManager = $this->createMock(VariableManager::class);
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->onlyMethods(['getName', 'getVariables', 'getDefinition'])
            ->setConstructorArgs([
                $doctrineHelper,
                $aclManager,
                $restrictionManager,
                null,
                $this->attributeManager,
                null,
                $variableManager
            ])->getMock();

        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects($this->any())
            ->method('getConfiguration')
            ->willReturn([]);

        $this->workflow->expects($this->any())
            ->method('getVariables')
            ->willReturn(new ArrayCollection());
        $this->workflow->expects($this->any())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);

        $this->normalizer = $this->getMockNormalizer();
    }

    public function testNormalize()
    {
        $variableName = 'test_variable';
        $denormalizedValue = 'denormalized_value';
        $normalizedValue = ['normalized_value'];
        $variable = $this->createVariable($variableName, 'string');

        $data = new WorkflowData();
        $data->set($variableName, $denormalizedValue);

        $this->normalizer->setSerializer($this->serializer);

        $this->serializer->expects($this->any())
            ->method('getWorkflow')
            ->willReturn($this->workflow);

        $this->attributeNormalizer->expects($this->once())
            ->method('supportsNormalization')
            ->with($this->workflow, $variable, $data->get($variableName))
            ->willReturn(true);

        $this->attributeNormalizer->expects($this->once())
            ->method('normalize')
            ->with($this->workflow, $variable, $data->get($variableName))
            ->willReturn($normalizedValue);

        $this->assertEquals(
            $normalizedValue,
            $this->normalizer->normalizeVariable($this->workflow, $variable, ['value' => $denormalizedValue])
        );
    }

    /**
     * @dataProvider denormalizeProvider
     */
    public function testDenormalize(
        Variable $variable,
        string $variableName,
        $expected,
        array $normalized,
        array $options = []
    ) {
        $expectedData = new WorkflowData();
        $expectedData->set($variableName, $expected);

        $normalizer = $this->getMockNormalizer($variable, $options);
        $normalizer->setSerializer($this->serializer);

        $this->serializer->expects($this->any())
            ->method('getWorkflow')
            ->willReturn($this->workflow);

        $this->attributeNormalizer->expects($this->any())
            ->method('supportsDenormalization')
            ->with($this->workflow, $variable, $normalized[$variableName])
            ->willReturn(true);

        $this->attributeNormalizer->expects($this->any())
            ->method('denormalize')
            ->with($this->workflow, $variable, $normalized[$variableName])
            ->willReturn($expectedData->get($variableName));

        $denormalized = $normalizer->denormalizeVariable($this->workflow, $variable, [
            'value' => $variable->getValue(),
            'options' => $variable->getOptions(),
            'property_path' => $variable->getPropertyPath()
        ]);
        $this->assertEquals($expected, $denormalized);
    }

    public function denormalizeProvider(): array
    {
        return [
            'string_value' => [
                'variable' => $this->createVariable('test_variable', 'string', 'normalized_value'),
                'variable_name' => 'test_variable',
                'expected' => 'denormalized_value',
                'normalized' => ['test_variable' => 'normalized_value']
            ],
            'array_value' => [
                'variable' => $this->createVariable('test_variable', 'array', ['item' => 'value']),
                'variable_name' => 'test_variable',
                'expected' => ['item' => 'value'],
                'normalized' => ['test_variable' => ['item' => 'value']]
            ],
            'object_value' => [
                'variable' => $this->createVariable(
                    'test_variable',
                    'object',
                    'en',
                    ['class' => 'stdClass'],
                    'code'
                ),
                'variable_name' => 'test_variable',
                'expected' => (object) ['code' => 'en'],
                'normalized' => ['test_variable' => (object) ['code' => 'en']]
            ],
            'entity_without_id_field' => [
                'variable' => $this->createVariable(
                    'test_variable',
                    'entity',
                    (object) ['id' => 1],
                    ['class' => 'stdClass', 'identifier' => 'id']
                ),
                'variable_name' => 'test_variable',
                'expected' => (object) ['id' => 1],
                'normalized' => ['test_variable' => ''],
                [
                    'isIdentifierComposite' => false,
                    'identifier' => ['id'],
                    'isUniqueField' => true
                ]
            ],
            'entity_id_field' => [
                'variable' => $this->createVariable(
                    'test_variable',
                    'entity',
                    (object) ['code' => 'en'],
                    ['class' => 'stdClass', 'identifier' => 'code']
                ),
                'variable_name' => 'test_variable',
                'expected' => (object) ['code' => 'en'],
                'normalized' => ['test_variable' => ''],
                [
                    'isIdentifierComposite' => false,
                    'identifier' => ['id'],
                    'isUniqueField' => true
                ]
            ],
        ];
    }

    /**
     * @dataProvider denormalizeExceptionsProvider
     */
    public function testDenormalizeExceptions(
        string $exception,
        string $message,
        Variable $variable,
        string $variableName,
        $expected,
        array $options = []
    ) {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $expectedData = new WorkflowData();
        $expectedData->set($variableName, $expected);

        $normalizer = $this->getMockNormalizer($variable, $options);
        $normalizer->setSerializer($this->serializer);

        $this->serializer->expects($this->any())
            ->method('getWorkflow')
            ->willReturn($this->workflow);

        $this->attributeNormalizer->expects($this->any())
            ->method('supportsDenormalization')
            ->willReturn(true);

        $this->attributeNormalizer->expects($this->any())
            ->method('denormalize')
            ->willReturn($expectedData->get($variableName));

        $normalizer->denormalizeVariable($this->workflow, $variable, [
            'value' => $variable->getValue(),
            'options' => $variable->getOptions(),
            'property_path' => $variable->getPropertyPath()
        ]);
    }

    public function denormalizeExceptionsProvider(): array
    {
        return [
            'entity_no_manager' => [
                SerializerException::class,
                'Can\'t get entity manager for class stdClass',
                'variable' => $this->createVariable(
                    'test_variable',
                    'entity',
                    (object) ['id' => 1],
                    ['class' => 'stdClass', 'identifier' => 'id']
                ),
                'variable_name' => 'test_variable',
                'expected' => (object) ['id' => 1],
                [
                    'getManagerForClass' => false,
                    'isIdentifierComposite' => false,
                    'identifier' => ['id'],
                    'isUniqueField' => true
                ]
            ],
            'entity_composite_id' => [
                SerializerException::class,
                'Entity with class stdClass has a composite identifier',
                'variable' => $this->createVariable(
                    'test_variable',
                    'entity',
                    (object) ['code' => 'en'],
                    ['class' => 'stdClass', 'identifier' => 'code']
                ),
                'variable_name' => 'test_variable',
                'expected' => (object) ['code' => 'en'],
                [
                    'isIdentifierComposite' => true,
                    'identifier' => ['id'],
                    'isUniqueField' => true
                ]
            ],
            'entity_not_unique_id' => [
                SerializerException::class,
                'Field code is not unique in entity with class stdClass',
                'variable' => $this->createVariable(
                    'test_variable',
                    'entity',
                    (object) ['code' => 'en'],
                    ['class' => 'stdClass', 'identifier' => 'code']
                ),
                'variable_name' => 'test_variable',
                'expected' => (object) ['code' => 'en'],
                [
                    'isIdentifierComposite' => false,
                    'identifier' => ['id'],
                    'isUniqueField' => false
                ]
            ],
        ];
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization(mixed $data, bool $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsNormalization($data, 'any_value'));
    }

    public function supportsNormalizationDataProvider(): array
    {
        return [
            [null, false],
            ['scalar', false],
            [new \DateTime(), false],
            [new WorkflowData(), true],
            [$this->createMock(WorkflowData::class), true],
        ];
    }

    /**
     * @dataProvider supportsDenormalizationDataProvider
     */
    public function testSupportsDenormalization(?string $type, bool $expected)
    {
        $this->assertEquals($expected, $this->normalizer->supportsDenormalization('any_value', $type));
    }

    public function supportsDenormalizationDataProvider(): array
    {
        return [
            ['', false],
            ['string', false],
            ['DateTime', false],
            [WorkflowData::class, true],
            [$this->getMockClass(WorkflowData::class), true],
        ];
    }

    public function normalizeDirectionDataProvider(): array
    {
        return [
            ['normalization'],
            ['denormalization'],
        ];
    }

    private function createVariable(
        string $name,
        string $type,
        $value = null,
        array $options = [],
        string $propertyPath = null
    ): Variable {
        $variable = new Variable();
        $variable
            ->setName($name)
            ->setType($type)
            ->setOptions($options)
            ->setPropertyPath($propertyPath)
            ->setValue($value)
            ->setLabel($name);

        return $variable;
    }

    private function getMockNormalizer(Variable $expected = null, array $options = []): WorkflowVariableNormalizer
    {
        if (!$expected instanceof Variable || 'entity' !== $expected->getType()) {
            $doctrine = $this->createMock(ManagerRegistry::class);
            $doctrine->expects($this->any())
                ->method('getManagerForClass')
                ->willReturn($this->createMock(ObjectManager::class));

            return new WorkflowVariableNormalizer([$this->attributeNormalizer], $doctrine);
        }

        $classMetadata = $this->createMock(ClassMetadataInfo::class);
        $classMetadata->isIdentifierComposite = $options['isIdentifierComposite'];
        $classMetadata->expects($this->any())
            ->method('getIdentifier')
            ->willReturn($options['identifier']);
        $classMetadata->expects($this->any())
            ->method('isUniqueField')
            ->willReturn($options['isUniqueField']);

        $fakeRepository = $this->createMock(EntityRepository::class);
        $fakeRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($expected->getValue());

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($fakeRepository);

        $getManagerForClass = $options['getManagerForClass'] ?? true;
        $managerForClass = $getManagerForClass ? $entityManager : null;

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($managerForClass);

        return new WorkflowVariableNormalizer([$this->attributeNormalizer], $doctrine);
    }
}
