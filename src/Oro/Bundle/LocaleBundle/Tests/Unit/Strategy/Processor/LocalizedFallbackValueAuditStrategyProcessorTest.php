<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Strategy\Processor;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizedFallbackValueRepository;
use Oro\Bundle\LocaleBundle\Strategy\Processor\LocalizedFallbackValueAuditStrategyProcessor;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizedFallbackValueParentStub;
use Oro\Bundle\LocaleBundle\Tools\GeneratorExtensions\DefaultFallbackGeneratorExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalizedFallbackValueAuditStrategyProcessorTest extends TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|MockObject
     */
    private $doctrine;

    /**
     * @var DefaultFallbackGeneratorExtension|MockObject
     */
    private $extension;

    private EntityAuditStrategyProcessorInterface $strategyProcessor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->extension = $this->createMock(DefaultFallbackGeneratorExtension::class);

        $this->strategyProcessor = new LocalizedFallbackValueAuditStrategyProcessor(
            $this->doctrine,
            $this->extension
        );
    }

    public function testProcessInverseCollectionsWithLocalizedFallbackValue(): void
    {
        $localizedId = 123;
        /** @var LocalizedFallbackValue $localizedField */
        $localizedField = $this->getEntity(LocalizedFallbackValue::class, ['id' => $localizedId]);

        $sourceEntityData = [
            'entity_class' => LocalizedFallbackValue::class,
            'entity_id' => $localizedId
        ];

        $this->getSourceEntity($sourceEntityData, $localizedField);
        $this->extension->expects($this->once())
            ->method('getFieldMap')
            ->willReturn([
                LocalizedFallbackValueParentStub::class => [
                    "localizedField" => "localizedFields"
                ]
            ]);

        $parentId = 234;
        $repository = $this->createMock(LocalizedFallbackValueRepository::class);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('getParentIdByFallbackValue')
            ->with(LocalizedFallbackValueParentStub::class, "localizedFields", $localizedField)
            ->willReturn($parentId);

        $fieldset = $this->strategyProcessor->processInverseCollections($sourceEntityData);

        $this->assertEquals(
            ['_assoc' => [
                'entity_class' => LocalizedFallbackValueParentStub::class,
                'field_name' => 'localizedFields',
                'entity_ids' => [$parentId]
            ]],
            $fieldset
        );
    }

    private function getSourceEntity(array $sourceEntityData, LocalizedFallbackValue $entity): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityMetaData = $this->createMock(ClassMetadata::class);
        $entityMetaData->associationMappings = [
            "localizedFields" => ['targetEntity' => LocalizedFallbackValue::class]
        ];

        $this->doctrine->expects($this->atLeastOnce())
            ->method('getManagerForClass')
            ->willReturn($entityManager);
        $entityManager->expects($this->atLeastOnce())
            ->method('getClassMetadata')
            ->willReturn($entityMetaData);
        $entityManager->expects($this->once())
            ->method('find')
            ->with($sourceEntityData['entity_class'], $sourceEntityData['entity_id'])
            ->willReturn($entity);
    }

    public function testProcessChangedEntities(): void
    {
        $sourceEntityData = [
            'entity_class' => LocalizedFallbackValue::class,
            'entity_id' => 234
        ];

        $result = $this->strategyProcessor->processChangedEntities($sourceEntityData);
        $this->assertEmpty($result);
    }

    public function testProcessInverseRelations(): void
    {
        $sourceEntityData = [
            'entity_class' => LocalizedFallbackValue::class,
            'entity_id' => 345
        ];

        $result = $this->strategyProcessor->processInverseRelations($sourceEntityData);
        $this->assertEmpty($result);
    }
}
