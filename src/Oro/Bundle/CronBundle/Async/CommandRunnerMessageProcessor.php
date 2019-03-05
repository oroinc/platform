<?php

namespace Oro\Bundle\CronBundle\Async;

use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * This processor is responsible for executing passed command with arguments.
 */
class CommandRunnerMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var JobRunner */
    private $jobRunner;

    /** @var LoggerInterface */
    private $logger;

    /** @var MessageProducerInterface */
    private $producer;

    /** @var CommandRunnerInterface */
    private $commandRunner;

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
     * @param CommandRunnerInterface $commandRunner
     */
    public function setCommandRunner(CommandRunnerInterface $commandRunner)
    {
        $this->commandRunner = $commandRunner;
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
        $commandName = $body['command'];

        $jobName = sprintf('oro:cron:run_command:%s', $commandName);
        if ($commandArguments) {
            array_walk($commandArguments, function ($item, $key) use (&$jobName) {
                if (is_array($item)) {
                    $item = implode(',', $item);
                }
                $jobName .= sprintf('-%s=%s', $key, $item);
            });
        }
        return $this->jobRunner->runUnique($ownerId, $jobName, function () use ($commandName, $commandArguments) {
            $output = $this->commandRunner->run($commandName, $commandArguments);
            $this->logger->info(sprintf('Command %s was executed. Output: %s', $commandName, $output), [
                'command' => $commandName,
                'arguments' => $commandArguments,
            ]);

            return true;
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RUN_COMMAND];
    }
}
