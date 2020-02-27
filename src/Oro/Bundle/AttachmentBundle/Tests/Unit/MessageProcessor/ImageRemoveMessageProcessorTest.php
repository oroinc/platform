<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\MessageProcessor;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\ImageRemovalManagerInterface;
use Oro\Bundle\AttachmentBundle\MessageProcessor\ImageRemoveMessageProcessor;
use Oro\Bundle\AttachmentBundle\Model\FileModel;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class ImageRemoveMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ImageRemovalManagerInterface
     */
    private $imageRemovalManager;

    /**
     * @var ImageRemoveMessageProcessor
     */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->imageRemovalManager = $this->createMock(ImageRemovalManagerInterface::class);

        $this->processor = new ImageRemoveMessageProcessor(
            $this->logger,
            $this->imageRemovalManager
        );
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals(['oro_attachment.remove_image'], ImageRemoveMessageProcessor::getSubscribedTopics()) ;
    }

    public function testProcess()
    {
        $session = new NullSession();
        $message = new NullMessage();

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
            $this->processor->process($message, $session)
        );
    }

    public function testProcessException()
    {
        $session = new NullSession();
        $message = new NullMessage();

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
            $this->processor->process($message, $session)
        );
    }
}
