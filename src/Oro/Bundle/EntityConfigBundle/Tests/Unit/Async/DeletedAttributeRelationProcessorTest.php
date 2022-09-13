<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Async;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Async\DeletedAttributeRelationProcessor;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProviderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class DeletedAttributeRelationProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = 'SomeClass';

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var DeletedAttributeProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $deletedAttributeProvider;

    /** @var DeletedAttributeRelationProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->deletedAttributeProvider = $this->createMock(DeletedAttributeProviderInterface::class);

        $this->processor = new DeletedAttributeRelationProcessor(
            $this->doctrineHelper,
            $this->logger,
            $this->deletedAttributeProvider
        );
    }

    public function testProcessDriverException(): void
    {
        $attributeFamilyId = 1;

        $message = new Message();
        $message->setBody([
            'attributeFamilyId' => $attributeFamilyId,
            'attributeNames' => [],
        ]);
        $message->setMessageId('someId');

        $attributeFamily = $this->getAttributeFamily($attributeFamilyId);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManagerForClass')
            ->with($attributeFamily->getEntityClass())
            ->willReturn($entityManager);
        $entityManager->expects(self::once())
            ->method('beginTransaction');
        $entityManager->expects(self::never())
            ->method('commit');
        $entityManager->expects(self::once())
            ->method('rollback');

        $exception = $this->createMock(DeadlockException::class);

        $this->deletedAttributeProvider->expects(self::once())
            ->method('removeAttributeValues')
            ->with($attributeFamily, [])
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during deleting attribute relation',
                ['exception' => $exception]
            );

        $session = $this->createMock(SessionInterface::class);
        $result = $this->processor->process(
            $message,
            $session
        );
        self::assertEquals(MessageProcessorInterface::REQUEUE, $result);
    }

    public function testProcessAnyException(): void
    {
        $attributeFamilyId = 1;

        $message = new Message();
        $message->setBody([
            'attributeFamilyId' => $attributeFamilyId,
            'attributeNames' => [],
        ]);
        $message->setMessageId('someId');

        $attributeFamily = $this->getAttributeFamily($attributeFamilyId);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManagerForClass')
            ->with($attributeFamily->getEntityClass())
            ->willReturn($entityManager);
        $entityManager->expects(self::once())
            ->method('beginTransaction');
        $entityManager->expects(self::never())
            ->method('commit');
        $entityManager->expects(self::once())
            ->method('rollback');

        $exception = new \Exception();

        $this->deletedAttributeProvider->expects(self::once())
            ->method('removeAttributeValues')
            ->with($attributeFamily, [])
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during deleting attribute relation',
                ['exception' => $exception]
            );

        $session = $this->createMock(SessionInterface::class);
        $result = $this->processor->process(
            $message,
            $session
        );
        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcess(): void
    {
        $attributeFamilyId = 1;

        $message = new Message();
        $message->setBody([
            'attributeFamilyId' => $attributeFamilyId,
            'attributeNames' => [],
        ]);
        $message->setMessageId('someId');

        $attributeFamily = $this->getAttributeFamily($attributeFamilyId);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManagerForClass')
            ->with($attributeFamily->getEntityClass())
            ->willReturn($entityManager);
        $entityManager->expects(self::once())
            ->method('beginTransaction');
        $entityManager->expects(self::once())
            ->method('commit');
        $entityManager->expects(self::never())
            ->method('rollback');

        $this->deletedAttributeProvider->expects(self::once())
            ->method('removeAttributeValues')
            ->with($attributeFamily, []);

        $this->logger->expects(self::never())
            ->method('error');

        $session = $this->createMock(SessionInterface::class);
        $result = $this->processor->process(
            $message,
            $session
        );
        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    private function getAttributeFamily(int $attributeFamilyId): AttributeFamily
    {
        $attributeFamily = new AttributeFamily();
        $attributeFamily->setEntityClass(self::ENTITY_CLASS);

        $repository = $this->createMock(AttributeFamilyRepository::class);
        $repository->expects(self::any())
            ->method('find')
            ->with($attributeFamilyId)
            ->willReturn($attributeFamily);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityRepositoryForClass')
            ->with(AttributeFamily::class)
            ->willReturn($repository);

        return $attributeFamily;
    }
}
