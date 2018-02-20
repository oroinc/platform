<?php
namespace Oro\Bundle\EmailBundle\Async;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
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
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Registry            $doctrine
     * @param AutoResponseManager $autoResponseManager
     * @param JobRunner $jobRunner
     * @param LoggerInterface     $logger
     */
    public function __construct(
        Registry $doctrine,
        AutoResponseManager $autoResponseManager,
        JobRunner $jobRunner,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->autoResponseManager = $autoResponseManager;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        if (! isset($data['jobId'], $data['id'])) {
            $this->logger->critical('Got invalid message');

            return self::REJECT;
        }

        /** @var Email $email */
        $email = $this->getEmailRepository()->find($data['id']);
        if (! $email) {
            $this->logger->error(sprintf('Email was not found. id: "%s"', $data['id']));

            return self::REJECT;
        }

        $result = $this->jobRunner->runDelayed($data['jobId'], function () use ($email) {
            $this->autoResponseManager->sendAutoResponses($email);

            return true;
        });

        return $result ? self::ACK : self::REJECT;
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
