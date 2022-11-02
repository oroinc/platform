<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Content;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\SyncBundle\Content\DoctrineTagGenerator;
use Oro\Bundle\SyncBundle\Tests\Unit\Content\Stub\EntityStub;
use Oro\Bundle\SyncBundle\Tests\Unit\Content\Stub\NewEntityStub;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\Form;

class DoctrineTagGeneratorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ENTITY_NAME = EntityStub::class;
    private const TEST_ENTITY_ALIAS = 'OroSyncBundle:EntityStub';
    private const TEST_NEW_ENTITY_NAME = NewEntityStub::class;
    private const TEST_ASSOCIATION_FIELD = 'testField';

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    private $uow;

    /** @var DoctrineTagGenerator */
    private $generator;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->uow = $this->createMock(UnitOfWork::class);

        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnCallback(function ($class) {
                $allowedClassNames = [self::TEST_ENTITY_NAME, self::TEST_ENTITY_ALIAS, self::TEST_NEW_ENTITY_NAME];
                if (in_array($class, $allowedClassNames, true)) {
                    return $this->em;
                }

                return null;
            });

        $entityClassResolver = $this->createMock(EntityClassResolver::class);
        $entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->willReturnMap([
                [self::TEST_ENTITY_ALIAS, self::TEST_ENTITY_NAME],
                [self::TEST_ENTITY_NAME, self::TEST_ENTITY_NAME],
                [self::TEST_NEW_ENTITY_NAME, self::TEST_NEW_ENTITY_NAME],
            ]);

        $this->generator = new DoctrineTagGenerator($doctrine, $entityClassResolver);
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(mixed $data, bool $expectedResult)
    {
        $this->assertSame($expectedResult, $this->generator->supports($data));
    }

    public function supportsDataProvider(): array
    {
        return [
            'real entity object given'           => [new EntityStub(), true],
            'real entity class name given'       => [self::TEST_ENTITY_NAME, true],
            'form instance with real data given' => [$this->getFormMock(new EntityStub()), true],
            'array given'                        => [['someKey' => 'test'], false],
            'some string given'                  => ['testString', false],
            'form with array given'              => [$this->getFormMock(['someKey' => 'test']), false],
        ];
    }

    /**
     * @dataProvider generateDataProvider
     */
    public function testGenerate(
        mixed $data,
        bool $includeCollectionTag,
        int $expectedCount,
        bool $isManaged = false
    ) {
        // only once if it's object
        $this->uow->expects($this->exactly(is_object($data) ? 1 : 0))
            ->method('getEntityState')
            ->willReturnCallback(function () use ($isManaged) {
                return $isManaged ? UnitOfWork::STATE_MANAGED : UnitOfWork::STATE_NEW;
            });
        $this->uow->expects($this->exactly((int)$isManaged))
            ->method('getEntityIdentifier')
            ->willReturn(['someIdentifierValue']);

        $result = $this->generator->generate($data, $includeCollectionTag);
        $this->assertCount($expectedCount, $result);
    }

    public function generateDataProvider(): array
    {
        return [
            'Should not generate any tags for new entity'                           => [
                new NewEntityStub(),
                false,
                0
            ],
            'Should not generate only collection tag for new entity'                => [
                new NewEntityStub(),
                true,
                1
            ],
            'Should generate one tag for managed entity'                            => [
                new EntityStub(),
                false,
                1,
                true
            ],
            'Should generate two tag for managed entity when collection asked'      => [
                new EntityStub(),
                true,
                2,
                true
            ],
            'Should not generate tag when data taken from string'                   => [
                self::TEST_ENTITY_NAME,
                false,
                0
            ],
            'Should generate collection tag when data taken from string'            => [
                self::TEST_ENTITY_NAME,
                true,
                1
            ],
            'Should take data from form and return tags for managed entity'         => [
                $this->getFormMock(new EntityStub()),
                true,
                2,
                true
            ],
            'Should take data from form and generate collection tag for new entity' => [
                $this->getFormMock(new NewEntityStub()),
                true,
                1,
                false
            ],
        ];
    }

    /**
     * @dataProvider generateFromAliasDataProvider
     */
    public function testGenerateFromAlias(string $data, array $expectedResult)
    {
        $configurationMock = $this->createMock(Configuration::class);
        $configurationMock->expects(self::any())
            ->method('getEntityNamespace')
            ->with('OroSyncBundle')
            ->willReturn('Oro\Bundle\SyncBundle\Tests\Unit\Content\Stub');

        $this->em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configurationMock);

        $result = $this->generator->generate($data, true);
        $this->assertEquals($expectedResult, $result);
    }

    public function generateFromAliasDataProvider(): array
    {
        return [
            'generate tag from fqcn' => [
                self::TEST_ENTITY_NAME,
                ['Oro_Bundle_SyncBundle_Tests_Unit_Content_Stub_EntityStub_type_collection'],
            ],
            'should generate same tag as for fqcn' => [
                self::TEST_ENTITY_ALIAS,
                ['Oro_Bundle_SyncBundle_Tests_Unit_Content_Stub_EntityStub_type_collection'],
            ],
        ];
    }

    /**
     * @dataProvider collectNestingDataDataProvider
     */
    public function testCollectNestingData(array $associations, array $mappings, int $expectedCount)
    {
        $testData = new EntityStub();
        $this->uow->expects($this->any())
            ->method('getEntityIdentifier')
            ->willReturn(['someIdentifierValue']);

        $metadata = new ClassMetadata(self::TEST_ENTITY_NAME);
        $metadata->associationMappings = $mappings;
        foreach ($associations as $name => $dataValue) {
            $field = $this->createMock(\ReflectionProperty::class);
            $field->expects($this->once())
                ->method('getValue')
                ->with($testData)
                ->willReturn($dataValue);
            $metadata->reflFields[$name] = $field;
        }

        $result = ReflectionUtil::callMethod($this->generator, 'collectNestedDataTags', [$testData, $metadata]);

        $this->assertIsArray($result, 'Should always return array');
        $this->assertCount($expectedCount, $result, 'Should not generate collection tag for associations');
    }

    public function collectNestingDataDataProvider(): array
    {
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $classMetadataMock = $this->createMock(ClassMetadata::class);

        return [
            'should not return any data when no association on entity' => [[], [], 0],
            'should collect one to one associations' => [
                [self::TEST_ASSOCIATION_FIELD => new EntityStub()],
                [self::TEST_ASSOCIATION_FIELD => ['type' => ClassMetadata::ONE_TO_ONE]],
                1
            ],
            'should collect all collection associations using persistent collection' => [
                [
                    self::TEST_ASSOCIATION_FIELD => new PersistentCollection(
                        $entityManagerMock,
                        $classMetadataMock,
                        new ArrayCollection(
                            [
                                new EntityStub(),
                                new EntityStub()
                            ]
                        )
                    )
                ],
                [self::TEST_ASSOCIATION_FIELD => ['type' => ClassMetadata::ONE_TO_MANY]],
                2
            ],
            'should collect all collection associations using array collection' => [
                [
                    self::TEST_ASSOCIATION_FIELD => new ArrayCollection(
                        [
                            new EntityStub(),
                            new EntityStub()
                        ]
                    )
                ],
                [self::TEST_ASSOCIATION_FIELD => ['type' => ClassMetadata::ONE_TO_MANY]],
                2
            ],
            'should process all associated values using persistent collection' => [
                [
                    self::TEST_ASSOCIATION_FIELD . '_1' => new PersistentCollection(
                        $entityManagerMock,
                        $classMetadataMock,
                        new ArrayCollection(
                            [
                                new EntityStub(),
                                new EntityStub()
                            ]
                        )
                    ),
                    self::TEST_ASSOCIATION_FIELD . '_2' => new EntityStub()
                ],
                [
                    self::TEST_ASSOCIATION_FIELD . '_1' => ['type' => ClassMetadata::ONE_TO_MANY],
                    self::TEST_ASSOCIATION_FIELD . '_2' => ['type' => ClassMetadata::ONE_TO_ONE]
                ],
                3
            ],
            'should process all associated values using array collection' => [
                [
                    self::TEST_ASSOCIATION_FIELD . '_1' => new ArrayCollection(
                        [
                            new EntityStub(),
                            new EntityStub()
                        ]
                    ),
                    self::TEST_ASSOCIATION_FIELD . '_2' => new EntityStub()
                ],
                [
                    self::TEST_ASSOCIATION_FIELD . '_1' => ['type' => ClassMetadata::ONE_TO_MANY],
                    self::TEST_ASSOCIATION_FIELD . '_2' => ['type' => ClassMetadata::ONE_TO_ONE]
                ],
                3
            ]
        ];
    }

    private function getFormMock(mixed $data): Form
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        return $form;
    }
}
