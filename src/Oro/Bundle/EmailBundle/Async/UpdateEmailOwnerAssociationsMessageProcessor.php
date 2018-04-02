<?php
namespace Oro\Bundle\EmailBundle\Async;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class UpdateEmailOwnerAssociationsMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var MessageProducerInterface
     */
    private $producer;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param MessageProducerInterface $producer
     * @param JobRunner $jobRunner
     * @param LoggerInterface $logger
     */
    public function __construct(MessageProducerInterface $producer, JobRunner $jobRunner, LoggerInterface $logger)
    {
        $this->producer = $producer;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        if (! isset($data['ownerClass'], $data['ownerIds']) || ! is_array($data['ownerIds'])) {
            $this->logger->critical('Got invalid message');

            return self::REJECT;
        }

        asort($data['ownerIds']);

        $jobName = sprintf(
            '%s:%s:%s',
            'oro.email.update_email_owner_associations',
            $data['ownerClass'],
            md5(implode(',', $data['ownerIds']))
        );

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobName,
            function (JobRunner $jobRunner) use ($data) {
                foreach ($data['ownerIds'] as $id) {
                    $jobRunner->createDelayed(
                        sprintf('%s:%s:%s', 'oro.email.update_email_owner_association', $data['ownerClass'], $id),
                        function (JobRunner $jobRunner, Job $child) use ($data, $id) {
                            $this->producer->send(Topics::UPDATE_EMAIL_OWNER_ASSOCIATION, [
                                'ownerId' => $id,
                                'ownerClass' => $data['ownerClass'],
                                'jobId' => $child->getId()
                            ]);
                        }
                    );
                }

                $this->logger->info(
                    sprintf('Sent "%s" messages', count($data['ownerIds'])),
                    ['data' => $data]
                );

                return true;
            }
        );

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::UPDATE_EMAIL_OWNER_ASSOCIATIONS];
    }
}
