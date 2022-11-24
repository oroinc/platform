<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Security;

use Oro\Bundle\MessageQueueBundle\Consumption\Exception\InvalidSecurityTokenException;
use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\Async\Topic\BasicMessageTestTopic;

class SecurityAwareConsumptionExtensionTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();

        self::purgeMessageQueue();
    }

    protected function tearDown(): void
    {
        self::getContainer()->get('security.token_storage')->setToken(null);

        self::purgeMessageQueue();
    }

    public function testWithValidToken(): void
    {
        $serializedToken = 'organizationId=1;userId=1;userClass=Oro\\Bundle\\UserBundle\\Entity\\User;roles=';
        $sentMessage = self::sendMessage(BasicMessageTestTopic::getName(), $this->createMessage($serializedToken));

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
    }

    public function testWithInvalidToken(): void
    {
        self::sendMessage(BasicMessageTestTopic::getName(), $this->createMessage('Invalid token;'));

        // This message stop consumer using an extension LimitConsumedMessagesExtension
        // and checks whether the consumer continues to work after an unsuccessful deserialization of the token
        self::sendMessage(BasicMessageTestTopic::getName(), ['message' => 'message']);

        $this->expectException(InvalidSecurityTokenException::class);
        $this->expectExceptionMessage('Security token is invalid');

        self::consume(1);

        self::consume(1);
    }

    private function createMessage(string $serializedToken = ''): Message
    {
        $message = new Message();
        $message->setProperties([SecurityAwareDriver::PARAMETER_SECURITY_TOKEN => $serializedToken]);
        $message->setBody([]);

        return $message;
    }
}
