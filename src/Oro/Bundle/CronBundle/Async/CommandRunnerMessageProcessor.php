<?php

namespace Oro\Bundle\CronBundle\Async;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * This processor is responsible for creating job for passed command with arguments and provides
 * real heavy work to be done separately from job creation.
 */
class CommandRunnerMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var JobRunner */
    private $jobRunner;

    /** @var LoggerInterface */
    private $logger;

    /** @var MessageProducerInterface */
    private $producer;

    /**
     * @param JobRunner                $jobRunner
     * @param LoggerInterface          $logger
     * @param MessageProducerInterface $producer
     */
    public function __construct(
        JobRunner $jobRunner,
        LoggerInterface $logger,
        MessageProducerInterface $producer
    ) {
        $this->jobRunner = $jobRunner;
        $this->logger    = $logger;
        $this->producer  = $producer;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (!isset($body['command'])) {
            $this->logger->critical('Got invalid message: empty command');

            return self::REJECT;
        }
        $commandArguments = [];
        if (isset($body['arguments'])) {
            $commandArguments = $body['arguments'];
        }
        if (!is_array($commandArguments)) {
            $this->logger->critical(
                'Got invalid message: "arguments" must be of type array',
                ['message' => $message]
            );

            return self::REJECT;
        }

        $result = $this->runRootJob($message->getMessageId(), $body, $commandArguments);

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param string $ownerId
     * @param array  $body
     * @param array  $commandArguments
     *
     * @return bool
     *
     * @throws \Oro\Component\MessageQueue\Transport\Exception\Exception
     */
    protected function runRootJob($ownerId, array $body, array $commandArguments)
    {
        $jobName = sprintf('oro:cron:run_command:%s', $body['command']);
        if ($commandArguments) {
            array_walk($commandArguments, function ($item, $key) use (&$jobName) {
                if (is_array($item)) {
                    $item = implode(',', $item);
                }

                $jobName .= sprintf('-%s=%s', $key, $item);
            });
        }

        $result  = $this->jobRunner->runUnique(
            $ownerId,
            $jobName,
            function (JobRunner $jobRunner) use ($body, $commandArguments, $jobName) {
                $jobRunner->createDelayed(
                    $jobName . '.delayed',
                    function (JobRunner $jobRunner, Job $child) use ($body) {
                        $body['jobId'] = $child->getId();
                        $this->producer->send(
                            Topics::RUN_COMMAND_DELAYED,
                            $body
                        );
                    }
                );

                return true;
            }
        );

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RUN_COMMAND];
    }
}
