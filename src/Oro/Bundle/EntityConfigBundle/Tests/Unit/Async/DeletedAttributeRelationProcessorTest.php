<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Async;

use Doctrine\DBAL\Driver\AbstractDriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Async\DeletedAttributeRelationProcessor;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProviderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class DeletedAttributeRelationProcessorTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_CLASS = 'SomeClass';

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var DeletedAttributeProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $deletedAttributeProvider;

    /**
     * @var DeletedAttributeRelationProcessor
     */
    protected $processor;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->deletedAttributeProvider = $this->createMock(DeletedAttributeProviderInterface::class);

        $this->processor = new DeletedAttributeRelationProcessor(
            $this->doctrineHelper,
            $this->logger,
            $this->deletedAttributeProvider
        );
    }

    public function testProcessWithoutAttributeFamilyId()
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode([]));
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepositoryForClass');
        $this->logger->expects($this->once())
            ->method('critical')
            ->with('Invalid message: key "attributeFamilyId" is missing.');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $result = $this->processor->process($message, $session);
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessDriverException()
    {
        $attributeFamilyId = 1;

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode([
                'attributeFamilyId' => $attributeFamilyId,
                'attributeNames' => [],
            ]));

        $attributeFamily = $this->getAttributeFamily($attributeFamilyId);
        $entityManager = $this->getEntityManagerMock($attributeFamily);
        $entityManager->expects($this->once())
            ->method('beginTransaction');
        $entityManager->expects($this->never())
            ->method('commit');
        $entityManager->expects($this->once())
            ->method('rollback');

        /** @var DeadlockException $exception */
        $exception = $this->createMock(DeadlockException::class);

        $this->deletedAttributeProvider->expects($this->once())
            ->method('removeAttributeValues')
            ->with($attributeFamily, [])
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Deleting attribute relation',
                ['exception' => $exception]
            );

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $result = $this->processor->process(
            $message,
            $session
        );
        $this->assertEquals(MessageProcessorInterface::REQUEUE, $result);
    }

    public function testProcessAnyException()
    {
        $attributeFamilyId = 1;

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode([
                'attributeFamilyId' => $attributeFamilyId,
                'attributeNames' => [],
            ]));

        $attributeFamily = $this->getAttributeFamily($attributeFamilyId);
        $entityManager = $this->getEntityManagerMock($attributeFamily);
        $entityManager->expects($this->once())
            ->method('beginTransaction');
        $entityManager->expects($this->never())
            ->method('commit');
        $entityManager->expects($this->once())
            ->method('rollback');

        $exception = new \Exception();

        $this->deletedAttributeProvider->expects($this->once())
            ->method('removeAttributeValues')
            ->with($attributeFamily, [])
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during Deleting attribute relation',
                ['exception' => $exception]
            );

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $result = $this->processor->process(
            $message,
            $session
        );
        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcess()
    {
        $attributeFamilyId = 1;

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn(JSON::encode([
                'attributeFamilyId' => $attributeFamilyId,
                'attributeNames' => [],
            ]));

        $attributeFamily = $this->getAttributeFamily($attributeFamilyId);
        $entityManager = $this->getEntityManagerMock($attributeFamily);
        $entityManager->expects($this->once())
            ->method('beginTransaction');
        $entityManager->expects($this->once())
            ->method('commit');
        $entityManager->expects($this->never())
            ->method('rollback');

        $this->deletedAttributeProvider->expects($this->once())
            ->method('removeAttributeValues')
            ->with($attributeFamily, []);

        $this->logger->expects($this->never())
            ->method('error');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $result = $this->processor->process(
            $message,
            $session
        );
        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @param int $attributeFamilyId
     * @return AttributeFamily
     */
    protected function getAttributeFamily($attributeFamilyId)
    {
        $attributeFamily = new AttributeFamily();
        $attributeFamily->setEntityClass(self::ENTITY_CLASS);

        $repository = $this->getMockBuilder(AttributeFamilyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('find')
            ->with($attributeFamilyId)
            ->willReturn($attributeFamily);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with(AttributeFamily::class)
            ->willReturn($repository);

        return $attributeFamily;
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getEntityManagerMock(AttributeFamily $attributeFamily)
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with($attributeFamily->getEntityClass())
            ->willReturn($entityManager);
        
        return $entityManager;
    }
}
