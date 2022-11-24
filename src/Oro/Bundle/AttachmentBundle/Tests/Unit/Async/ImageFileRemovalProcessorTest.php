<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Async;

use Oro\Bundle\AttachmentBundle\Async\ImageFileRemovalProcessor;
use Oro\Bundle\AttachmentBundle\Async\Topic\AttachmentRemoveImageTopic;
use Oro\Bundle\AttachmentBundle\Manager\FileRemovalManagerInterface;
use Oro\Bundle\AttachmentBundle\Model\FileModel;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class ImageFileRemovalProcessorTest extends \PHPUnit\Framework\TestCase
{
    private FileRemovalManagerInterface|\PHPUnit\Framework\MockObject\MockObject $imageRemovalManager;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private ImageFileRemovalProcessor $processor;

    protected function setUp(): void
    {
        $this->imageRemovalManager = $this->createMock(FileRemovalManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new ImageFileRemovalProcessor(
            $this->imageRemovalManager,
            $this->logger
        );
    }

    private function getSession(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals([AttachmentRemoveImageTopic::getName()], ImageFileRemovalProcessor::getSubscribedTopics());
    }

    public function testProcess(): void
    {
        $message = new Message();
        $message->setBody([
            'images' => [
                [
                    'id' => 1,
                    'fileName' => '12345.jpg',
                    'originalFileName' => 'orig_name.jpg',
                    'parentEntityClass' => 'Test\Entity'
                ],
            ],
        ]);

        $file = new FileModel();
        $file->setId(1);
        $file->setFilename('12345.jpg');
        $file->setOriginalFilename('orig_name.jpg');
        $file->setParentEntityClass('Test\Entity');
        $file->setExtension('jpg');

        $this->imageRemovalManager->expects(self::once())
            ->method('removeFiles')
            ->with($file);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessException(): void
    {
        $message = new Message();
        $message->setBody([
            'images' => [
                [
                    'id' => 2,
                    'fileName' => '12345.jpg',
                    'originalFileName' => 'orig_name.jpg',
                    'parentEntityClass' => 'Test\Entity'
                ],
            ],
        ]);

        $file = new FileModel();
        $file->setId(2);
        $file->setFilename('12345.jpg');
        $file->setOriginalFilename('orig_name.jpg');
        $file->setParentEntityClass('Test\Entity');
        $file->setExtension('jpg');

        $exception = new \RuntimeException('Error');
        $this->imageRemovalManager->expects(self::once())
            ->method('removeFiles')
            ->with($file)
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with('Unable to remove image 12345.jpg', ['exception' => $exception]);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessWithEmptyOriginalFileName(): void
    {
        $fileName = '12345.jpg';

        $message = new Message();
        $message->setBody([
            'images' => [
                [
                    'id' => 2,
                    'fileName' => $fileName,
                    'originalFileName' => $fileName,
                    'parentEntityClass' => 'Test\Entity'
                ],
            ],
        ]);

        $file = new FileModel();
        $file->setId(2);
        $file->setFilename($fileName);
        $file->setOriginalFilename($fileName);
        $file->setParentEntityClass('Test\Entity');
        $file->setExtension('jpg');

        $this->imageRemovalManager->expects(self::once())
            ->method('removeFiles')
            ->with($file);
        $this->logger->expects(self::never())
            ->method('warning');

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }
}
