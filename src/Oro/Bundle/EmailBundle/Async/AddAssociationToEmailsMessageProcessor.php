<?php
namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\EmailBundle\Async\Manager\AssociationManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class AddAssociationToEmailsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
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

        if (! isset($data['emailIds'], $data['targetClass'], $data['targetId']) || ! is_array($data['emailIds'])) {
            $this->logger->critical(sprintf(
                '[AddAssociationToEmailsMessageProcessor] Got invalid message: "%s"',
                $message->getBody()
            ));

            return self::REJECT;
        }

        $this->associationManager->processAddAssociation($data['emailIds'], $data['targetClass'], $data['targetId']);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::ADD_ASSOCIATION_TO_EMAILS];
    }
}
