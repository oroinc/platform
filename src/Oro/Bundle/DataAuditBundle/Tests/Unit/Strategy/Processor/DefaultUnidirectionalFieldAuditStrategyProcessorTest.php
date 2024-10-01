<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Strategy\Processor;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\DefaultUnidirectionalFieldAuditStrategyProcessor;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultUnidirectionalFieldAuditStrategyProcessorTest extends TestCase
{
    use EntityTrait;

    protected ManagerRegistry|MockObject $doctrine;

    protected EntityAuditStrategyProcessorInterface $strategyProcessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->strategyProcessor = new DefaultUnidirectionalFieldAuditStrategyProcessor($this->doctrine);
    }

    public function testProcessInverseCollectionsWithUnidirectionalFieldEntity(): void
    {
        $attachmentId = 123;
        $attachment = $this->getEntity(Attachment::class, ['id' => $attachmentId]);
        $userId = 234;
        $user = $this->getEntity(UserStub::class, ['id' => $userId]);
        $attachment->setOwner($user);

        $sourceEntityData = [
            'entity_class' => Attachment::class,
            'entity_id' => $attachmentId
        ];

        $this->assertGetSourceEntity($sourceEntityData, $attachment);

        $fieldset = $this->strategyProcessor->processInverseCollections($sourceEntityData);

        $this->assertEquals(
            ['owner' => [
                'entity_class' => UserStub::class,
                'field_name' => UnidirectionalFieldHelper::createUnidirectionalField(
                    UserStub::class,
                    class_exists('Oro\Bundle\MakerBundle\Helper\TranslationHelper')
                        ? 'oro.attachment.entity_label' : 'Attachment'
                ),
                'entity_ids' => [$userId]
            ]],
            $fieldset
        );
    }

    private function assertGetSourceEntity(array $sourceEntityData, Attachment $entity): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityMetaData = $this->createMock(ClassMetadata::class);
        $entityMetaData->associationMappings = [
            "owner" => ['targetEntity' => UserStub::class]
        ];

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);
        $entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($entityMetaData);
        $entityManager->expects(self::once())
            ->method('find')
            ->with($sourceEntityData['entity_class'], $sourceEntityData['entity_id'])
            ->willReturn($entity);
        $entityMetaData->expects(self::once())
            ->method('getFieldValue')
            ->with($entity, 'owner')
            ->willReturn($entity->getOwner());
    }

    public function testProcessChangedEntities(): void
    {
        $sourceEntityData = [
            'entity_class' => Attachment::class,
            'entity_id' => 234
        ];

        $result = $this->strategyProcessor->processChangedEntities($sourceEntityData);
        $this->assertSame($sourceEntityData, $result);
    }

    public function testProcessInverseRelations(): void
    {
        $sourceEntityData = [
            'entity_class' => Attachment::class,
            'entity_id' => 345
        ];

        $result = $this->strategyProcessor->processInverseRelations($sourceEntityData);
        $this->assertEmpty($result);
    }
}
