<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Batch\Handler\AsyncOperationDeleteHandler;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Exception\DeleteAsyncOperationException;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteAccessDeniedExceptionFactory;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtension;
use Oro\Bundle\EntityBundle\Handler\EntityDeleteHandlerExtensionRegistry;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Log\LoggerInterface;

class AsyncOperationDeleteHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FileManager */
    private $fileManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManagerInterface */
    private $em;

    /** @var AsyncOperationDeleteHandler */
    private $deleteHandler;

    protected function setUp(): void
    {
        $extension = new EntityDeleteHandlerExtension();
        $extensionRegistry = $this->createMock(EntityDeleteHandlerExtensionRegistry::class);
        $extensionRegistry->expects(self::any())
            ->method('getHandlerExtension')
            ->with(AsyncOperation::class)
            ->willReturn($extension);

        $this->fileManager = $this->createMock(FileManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(AsyncOperation::class)
            ->willReturn($this->em);

        $this->deleteHandler = new AsyncOperationDeleteHandler(
            new FileNameProvider(),
            $this->fileManager,
            $this->logger
        );
        $this->deleteHandler->setAccessDeniedExceptionFactory(new EntityDeleteAccessDeniedExceptionFactory());
        $this->deleteHandler->setDoctrine($doctrine);
        $this->deleteHandler->setExtensionRegistry($extensionRegistry);
    }

    private function getAsyncOperation(int $id): AsyncOperation
    {
        $operation = new AsyncOperation();
        ReflectionUtil::setId($operation, $id);

        return $operation;
    }
    public function testIsDeleteGranted()
    {
        $operation = $this->getAsyncOperation(234);
        self::assertTrue($this->deleteHandler->isDeleteGranted($operation));
    }

    public function testHandleDelete()
    {
        $operation = $this->getAsyncOperation(234);

        $this->fileManager->expects(self::once())
            ->method('findFiles')
            ->with('api_234_')
            ->willReturn(['api_234_first', 'api_234_first_second']);

        $this->fileManager->expects(self::exactly(2))
            ->method('deleteFile')
            ->willReturnMap([['api_234_first'], ['api_234_first_second']]);

        $this->em->expects(self::once())
            ->method('remove')
            ->with($operation);

        $this->em->expects(self::once())
            ->method('flush');

        $this->logger->expects(self::never())
            ->method('warning');

        $this->deleteHandler->delete($operation);
    }

    public function testHandleDeleteWhenExceptionOccurredOnFindFiles()
    {
        $this->expectException(DeleteAsyncOperationException::class);
        $this->expectExceptionMessage('Failed to delete all files related to the asynchronous operation.');

        $operation = $this->getAsyncOperation(234);

        $exception = new \Exception('fail');
        $this->fileManager->expects(self::once())
            ->method('findFiles')
            ->with('api_234_')
            ->willThrowException($exception);

        $this->fileManager->expects(self::never())
            ->method('deleteFile');

        $this->em->expects(self::never())
            ->method('remove');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The finding of files for entity 234 is failed.',
                ['exception' => $exception]
            );

        $this->em->expects(self::never())
            ->method('flush');

        $this->deleteHandler->delete($operation);
    }

    public function testHandleDeleteWhenExceptionOccurredOnDeleteFiles()
    {
        $this->expectException(DeleteAsyncOperationException::class);
        $this->expectExceptionMessage('Failed to delete all files related to the asynchronous operation.');

        $operation = $this->getAsyncOperation(234);

        $exception = new \Exception('fail');
        $this->fileManager->expects(self::once())
            ->method('findFiles')
            ->with('api_234_')
            ->willReturn(['api_234_first']);

        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->willThrowException($exception);

        $this->em->expects(self::never())
            ->method('remove');

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'The deletion of the file "api_234_first" failed.',
                ['exception' => $exception]
            );

        $this->em->expects(self::never())
            ->method('flush');

        $this->deleteHandler->delete($operation);
    }
}
