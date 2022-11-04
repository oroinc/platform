<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Async;

use Oro\Bundle\AttachmentBundle\Async\Topic\AttachmentRemoveImageTopic;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class ImageFileRemovalProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();
    }

    public function testProcess(): void
    {
        $sentMessage = self::sendMessage(
            AttachmentRemoveImageTopic::getName(),
            [
                'images' => [
                    [
                        'id' => PHP_INT_MAX,
                        'fileName' => 'test1',
                        'originalFileName' => 'test1',
                        'parentEntityClass' => \stdClass::class,
                    ],
                ],
            ]
        );
        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_attachment.async.image_file_removal_processor',
            $sentMessage
        );
    }
}
