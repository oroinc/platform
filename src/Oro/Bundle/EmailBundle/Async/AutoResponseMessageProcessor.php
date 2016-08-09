<?php
namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class AutoResponseMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var AutoResponseManager
     */
    private $autoResponseManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Registry            $doctrine
     * @param AutoResponseManager $autoResponseManager
     * @param LoggerInterface     $logger
     */
    public function __construct(Registry $doctrine, AutoResponseManager $autoResponseManager, LoggerInterface $logger)
    {
        $this->doctrine = $doctrine;
        $this->autoResponseManager = $autoResponseManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        if (! isset($data['ids']) || ! is_array($data['ids'])) {
            $this->logger->critical(sprintf(
                '[AutoResponseMessageProcessor] Got invalid message. "%s"',
                $message->getBody()
            ));

            return self::REJECT;
        }

        foreach ($data['ids'] as $id) {
            /** @var Email $email */
            $email = $this->getEmailRepository()->find($id);
            if (! $email) {
                $this->logger->error(sprintf(
                    '[AutoResponseMessageProcessor] Email was not found. id: "%s"',
                    $id
                ));

                continue;
            }

            $this->autoResponseManager->sendAutoResponses($email);
        }

        return self::ACK;
    }

    /**
     * @return EntityRepository
     */
    protected function getEmailRepository()
    {
        return $this->doctrine->getRepository(Email::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SEND_AUTO_RESPONSE];
    }
}
