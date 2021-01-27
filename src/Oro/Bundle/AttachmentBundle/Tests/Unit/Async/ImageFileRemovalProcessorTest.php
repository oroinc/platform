<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Async;

use Oro\Bundle\AttachmentBundle\Async\ImageFileRemovalProcessor;
use Oro\Bundle\AttachmentBundle\Manager\FileRemovalManagerInterface;
use Oro\Bundle\AttachmentBundle\Model\FileModel;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class ImageFileRemovalProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileRemovalManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imageRemovalManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ImageFileRemovalProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->imageRemovalManager = $this->createMock(FileRemovalManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new ImageFileRemovalProcessor(
            $this->imageRemovalManager,
            $this->logger
        );
    }

    /**
     * @return SessionInterface
     */
    private function getSession()
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(['oro_attachment.remove_image'], ImageFileRemovalProcessor::getSubscribedTopics());
    }

    public function testProcess()
    {
        $message = new Message();
        $message->setBody(JSON::encode([
            [
                'id' => 1,
                'fileName' => '12345.jpg',
                'originalFileName' => 'orig_name.jpg',
                'parentEntityClass' => ProductImage::class
            ]
        ]));

        $file = new FileModel();
        $file->setId(1);
        $file->setFilename('12345.jpg');
        $file->setOriginalFilename('orig_name.jpg');
        $file->setParentEntityClass(ProductImage::class);
        $file->setExtension('jpg');

        $this->imageRemovalManager->expects($this->once())
            ->method('removeFiles')
            ->with($file);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessException()
    {
        $message = new Message();
        $message->setBody(JSON::encode([
            [
                'id' => 2,
                'fileName' => '12345.jpg',
                'originalFileName' => 'orig_name.jpg',
                'parentEntityClass' => ProductImage::class
            ]
        ]));

        $file = new FileModel();
        $file->setId(2);
        $file->setFilename('12345.jpg');
        $file->setOriginalFilename('orig_name.jpg');
        $file->setParentEntityClass(ProductImage::class);
        $file->setExtension('jpg');

        $exception = new \RuntimeException('Error');
        $this->imageRemovalManager->expects($this->once())
            ->method('removeFiles')
            ->with($file)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Unable to remove image 12345.jpg', ['exception' => $exception]);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessWithEmptyOriginalFileName()
    {
        $fileName = '12345.jpg';

        $message = new Message();
        $message->setBody(JSON::encode([
            [
                'id' => 2,
                'fileName' => $fileName,
                'originalFileName' => null,
                'parentEntityClass' => ProductImage::class
            ]
        ]));

        $file = new FileModel();
        $file->setId(2);
        $file->setFilename($fileName);
        $file->setOriginalFilename($fileName);
        $file->setParentEntityClass(ProductImage::class);
        $file->setExtension('jpg');

        $this->imageRemovalManager->expects($this->once())
            ->method('removeFiles')
            ->with($file);
        $this->logger->expects($this->never())
            ->method('warning');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessWithInvalidMessage()
    {
        $message = new Message();
        $message->setBody(JSON::encode([
            [
                'id' => 2,
                'fileName' => null,
                'originalFileName' => null,
                'parentEntityClass' => ProductImage::class
            ]
        ]));

        $this->imageRemovalManager->expects($this->never())
            ->method('removeFiles');
        $this->logger->expects($this->once())
            ->method('warning');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }
}
