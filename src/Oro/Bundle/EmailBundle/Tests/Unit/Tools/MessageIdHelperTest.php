<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Tools\MessageIdHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Message;

class MessageIdHelperTest extends TestCase
{
    public function testGetMessageId(): void
    {
        $message = new Message();
        $message->getHeaders()
            ->addHeader('From', ['from@example.org'])
            ->addHeader('Message-ID', 'sample/message/id@example.org');

        self::assertEquals('<sample/message/id@example.org>', MessageIdHelper::getMessageId($message));
    }

    public function testGetTransportMessageIdFromCustomHeader(): void
    {
        $message = new Message();
        $message->getHeaders()
            ->addHeader('From', ['from@example.org'])
            ->addHeader('Message-ID', 'original/message/id@example.org')
            ->addTextHeader('X-Oro-Message-ID', 'transport-message-id');

        self::assertEquals('<transport-message-id>', MessageIdHelper::getTransportMessageId($message));
    }

    public function testGetTransportMessageIdFromCustomHeaderAlreadyWrapped(): void
    {
        $message = new Message();
        $message->getHeaders()
            ->addHeader('From', ['from@example.org'])
            ->addHeader('Message-ID', 'original/message/id@example.org')
            ->addTextHeader('X-Oro-Message-ID', '<transport-message-id>');

        self::assertEquals('<transport-message-id>', MessageIdHelper::getTransportMessageId($message));
    }

    public function testGetTransportMessageIdFallbackToStandardHeader(): void
    {
        $message = new Message();
        $message->getHeaders()
            ->addHeader('From', ['from@example.org'])
            ->addHeader('Message-ID', 'sample/message/id@example.org');

        // Should fallback to standard Message-ID header if X-Oro-Message-ID is not present
        self::assertEquals('<sample/message/id@example.org>', MessageIdHelper::getTransportMessageId($message));
    }

    /**
     * @dataProvider unwrapMessageIdDataProvider
     */
    public function testUnwrapMessageId(string $messageId, string $expectedMessageId): void
    {
        self::assertEquals(
            $expectedMessageId,
            MessageIdHelper::unwrapMessageId($messageId)
        );
    }

    public function unwrapMessageIdDataProvider(): array
    {
        return [
            ['', ''],
            ['<sample/message/id@example.org>', 'sample/message/id@example.org'],
            ['sample/message/id@example.org', 'sample/message/id@example.org'],
        ];
    }
}
