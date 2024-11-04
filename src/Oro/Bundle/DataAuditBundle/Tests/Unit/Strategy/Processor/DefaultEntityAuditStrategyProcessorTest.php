<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Strategy\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\DefaultEntityAuditStrategyProcessor;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Stub\CustomFieldStub;
use Oro\Component\Testing\ReflectionUtil;

class DefaultEntityAuditStrategyProcessorTest extends \PHPUnit\Framework\TestCase
{
    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $doctrine;

    private EntityAuditStrategyProcessorInterface $strategyProcessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->strategyProcessor = new DefaultEntityAuditStrategyProcessor($this->doctrine);
    }

    public function testProcessWithCustomLocalizedFieldStub(): void
    {
        $customFieldId = 123;
        $customField = new CustomFieldStub();
        ReflectionUtil::setId($customField, $customFieldId);

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
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityMetaData = $this->createMock(ClassMetadata::class);
        $entityMetaData->associationMappings = [
            'customFields' => ['targetEntity' => CustomFieldStub::class]
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
