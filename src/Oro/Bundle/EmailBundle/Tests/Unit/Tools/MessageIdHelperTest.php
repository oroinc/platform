<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Tools\MessageIdHelper;
use Symfony\Component\Mime\Message;

class MessageIdHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testGetMessageId(): void
    {
        $message = new Message();
        $message->getHeaders()
            ->addHeader('From', ['from@example.org'])
            ->addHeader('Message-ID', 'sample/message/id@example.org');

        self::assertEquals('<sample/message/id@example.org>', MessageIdHelper::getMessageId($message));
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
