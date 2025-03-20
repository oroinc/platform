<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Security;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SecurityBundle\Exception\InvalidTokenSerializationException;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\Async\Topic\BasicMessageTestTopic;
use Oro\Component\MessageQueue\Test\Async\Topic\RequeueTestTopic;

class SecurityAwareConsumptionExtensionTest extends WebTestCase
{
    use MessageQueueExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUserData::class]);

        self::purgeMessageQueue();
    }

    #[\Override]
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

        $this->expectException(InvalidTokenSerializationException::class);
        $this->expectExceptionMessage('An error occurred while deserializing the token.');

        self::consume(1);

        self::consume(1);
    }

    public function testWithInvalidTokenAndRedelivered(): void
    {
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        $serializedToken = 'organizationId=1;userId=%s;userClass=Oro\\Bundle\\UserBundle\\Entity\\User;roles=';
        $serializedToken = sprintf($serializedToken, $user->getId());

        // Simulate the situation when a message is re-queued.
        self::sendMessage(RequeueTestTopic::getName(), $this->createMessage($serializedToken));

        self::consume();
        $requeueMessages = self::getProcessedMessagesByStatus(MessageProcessorInterface::REQUEUE);
        self::assertCount(1, $requeueMessages);

        // Remove the user and start the consumer. Expect the security token to be invalid and the 're-queue' message
        // to be rejected without error.
        $this->removeUser($user->getId());
        self::assertEmpty(self::getProcessedMessagesByStatus(MessageProcessorInterface::REJECT));

        self::consume();
        $rejectMessages = self::getProcessedMessagesByStatus(MessageProcessorInterface::REJECT);
        self::assertCount(1, $rejectMessages);
    }

    public function testWithInvalidUserAndOrganization(): void
    {
        $serializedToken = 'organizationId=100;userId=50;userClass=Oro\\Bundle\\UserBundle\\Entity\\User;roles=';
        $sentMessage = self::sendMessage(BasicMessageTestTopic::getName(), $this->createMessage($serializedToken));
        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
    }

    private function createMessage(string $serializedToken = ''): Message
    {
        $message = new Message();
        $message->setProperties([SecurityAwareDriver::PARAMETER_SECURITY_TOKEN => $serializedToken]);
        $message->setBody([]);

        return $message;
    }

    private function removeUser(int $userId): void
    {
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = $this->getContainer()->get('doctrine');
        $entityManager = $managerRegistry->getManager();
        $originalUser = $managerRegistry->getRepository(User::class)->find($userId);
        if ($originalUser) {
            $entityManager->remove($originalUser);
            $entityManager->flush($originalUser);
            $entityManager->clear();
        }
    }
}
