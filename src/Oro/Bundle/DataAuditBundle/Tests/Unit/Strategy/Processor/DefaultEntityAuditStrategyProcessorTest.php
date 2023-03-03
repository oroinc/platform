<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Strategy\Processor;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\DefaultEntityAuditStrategyProcessor;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Stub\CustomFieldStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultEntityAuditStrategyProcessorTest extends TestCase
{
    use EntityTrait;

    private ManagerRegistry|MockObject $doctrine;

    private EntityAuditStrategyProcessorInterface $strategyProcessor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->strategyProcessor = new DefaultEntityAuditStrategyProcessor($this->doctrine);
    }

    public function testProcessWithCustomLocalizedFieldStub(): void
    {
        $customFieldId = 123;
        $customField = $this->getEntity(CustomFieldStub::class, ['id' => $customFieldId]);

        $sourceEntityData = [
            'entity_class' => CustomFieldStub::class,
            'entity_id' => $customFieldId
        ];

        $this->getSourceEntity($sourceEntityData, $customField);

        $fieldset = $this->strategyProcessor->processInverseCollections($sourceEntityData);

        $this->assertEquals([], $fieldset);
    }

    private function getSourceEntity(array $sourceEntityData, CustomFieldStub $entity)
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityMetaData = $this->createMock(ClassMetadata::class);
        $entityMetaData->associationMappings = [
            "customFields" => ['targetEntity' => CustomFieldStub::class]
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
}
