<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Async;

use Oro\Bundle\AttachmentBundle\Async\RemoveImageMessageProcessor;
use Oro\Bundle\AttachmentBundle\Manager\ImageRemovalManagerInterface;
use Oro\Bundle\AttachmentBundle\Model\FileModel;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class RemoveImageMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImageRemovalManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imageRemovalManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var RemoveImageMessageProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->imageRemovalManager = $this->createMock(ImageRemovalManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new RemoveImageMessageProcessor(
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
        $this->assertEquals(['oro_attachment.remove_image'], RemoveImageMessageProcessor::getSubscribedTopics());
    }

    public function testProcess()
    {
        $message = new Message();

        $images = [
            [
                'id' => 1,
                'fileName' => '12345.jpg',
                'originalFileName' => 'orig_name.jpg',
                'parentEntityClass' => ProductImage::class
            ]
        ];
        $message->setBody(JSON::encode($images));

        $file = new FileModel();
        $file->setId(1);
        $file->setFilename('12345.jpg');
        $file->setOriginalFilename('orig_name.jpg');
        $file->setParentEntityClass(ProductImage::class);

        $this->imageRemovalManager->expects($this->once())
            ->method('removeImageWithVariants')
            ->with($file);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }

    public function testProcessException()
    {
        $message = new Message();

        $images = [
            [
                'id' => 2,
                'fileName' => '12345.jpg',
                'originalFileName' => 'orig_name.jpg',
                'parentEntityClass' => ProductImage::class
            ]
        ];
        $message->setBody(JSON::encode($images));

        $file = new FileModel();
        $file->setId(2);
        $file->setFilename('12345.jpg');
        $file->setOriginalFilename('orig_name.jpg');
        $file->setParentEntityClass(ProductImage::class);

        $exception = new \RuntimeException('Error');
        $this->imageRemovalManager->expects($this->once())
            ->method('removeImageWithVariants')
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
        $message = new Message();

        $fileName = '12345.jpg';
        $images = [
            [
                'id' => 2,
                'fileName' => $fileName,
                'originalFileName' => null,
                'parentEntityClass' => ProductImage::class
            ]
        ];
        $message->setBody(JSON::encode($images));

        $file = new FileModel();
        $file->setId(2);
        $file->setFilename($fileName);
        $file->setOriginalFilename($fileName);
        $file->setParentEntityClass(ProductImage::class);

        $this->imageRemovalManager->expects($this->once())
            ->method('removeImageWithVariants')
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

        $images = [
            [
                'id' => 2,
                'fileName' => null,
                'originalFileName' => null,
                'parentEntityClass' => ProductImage::class
            ]
        ];
        $message->setBody(JSON::encode($images));

        $this->imageRemovalManager->expects($this->never())
            ->method('removeImageWithVariants');
        $this->logger->expects($this->once())
            ->method('warning');

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSession())
        );
    }
}
