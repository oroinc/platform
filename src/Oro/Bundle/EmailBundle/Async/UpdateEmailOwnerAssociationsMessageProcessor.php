<?php
namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class UpdateEmailOwnerAssociationsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var AssociationManager
     */
    private $associationManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param AssociationManager $associationManager
     * @param LoggerInterface    $logger
     */
    public function __construct(AssociationManager $associationManager, LoggerInterface $logger)
    {
        $this->associationManager = $associationManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        if (! isset($data['ownerClass'], $data['ownerIds']) || ! is_array($data['ownerIds'])) {
            $this->logger->critical(sprintf(
                '[UpdateEmailOwnerAssociationsMessageProcessor] Got invalid message: "%s"',
                $message->getBody()
            ));

            return self::REJECT;
        }

        $messagesCount = $this->associationManager->processUpdateEmailOwner($data['ownerClass'], $data['ownerIds']);

        $this->logger->info(sprintf(
            '[UpdateEmailOwnerAssociationsMessageProcessor] Sent "%s" messages',
            $messagesCount
        ));

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::UPDATE_EMAIL_OWNER_ASSOCIATIONS];
    }
}
