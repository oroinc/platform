<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Strategy;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Strategy\AttachmentAuditStrategyProcessor;
use Oro\Bundle\DataAuditBundle\Strategy\Processor\EntityAuditStrategyProcessorInterface;
use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttachmentAuditStrategyProcessorTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityAuditStrategyProcessorInterface $strategyProcessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->strategyProcessor = new AttachmentAuditStrategyProcessor($this->doctrine);
    }

    public function testProcessInverseCollectionsWithDeletedUnidirectionalFieldEntity(): void
    {
        $attachmentId = 123;
        $attachment = new Attachment();
        ReflectionUtil::setId($attachment, $attachmentId);
        $userId = 234;
        $user = new UserStub($userId);
        $attachment->setOwner($user);

        $sourceEntityData = [
            'entity_class' => Attachment::class,
            'entity_id' => $attachmentId,
            'change_set' => [
                'owner' => [['entity_id' => $userId, 'entity_class' => UserStub::class], null]
            ]
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
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityMetaData = $this->createMock(ClassMetadata::class);
        $entityRepo = $this->createMock(EntityRepository::class);
        $entityMetaData->associationMappings = [
            'owner' => ['targetEntity' => UserStub::class]
        ];

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->willReturn($entityRepo);
        $entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($entityMetaData);
        $entityManager->expects(self::once())
            ->method('find')
            ->with($sourceEntityData['entity_class'], $sourceEntityData['entity_id'])
            ->willReturn(null);
        $entityRepo->expects(self::once())
            ->method('find')
            ->with($entity->getOwner()->getId())
            ->willReturn($entity->getOwner());
    }
}
