<?php

namespace Oro\Bundle\CronBundle\Async;

use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * This processor is responsible for executing passed command with arguments.
 *
 * Subscribe to `oro.cron.run_command.delayed` to prevent exceptions due to BC break
 * if such messages is in message broker they should be executed and processed with method `runDelayedJob`
 */
class CommandRunnerProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var JobRunner */
    private $jobRunner;

    /** @var CommandRunnerInterface */
    private $commandRunner;

    public function __construct(JobRunner $jobRunner, CommandRunnerInterface $commandRunner)
    {
        $this->jobRunner = $jobRunner;
        $this->commandRunner = $commandRunner;
    }

    /**
     * {@inheritdoc}
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

        if (array_key_exists('jobId', $body)) {
            $result = $this->runDelayedJob($body['jobId'], $body['command'], $commandArguments);
        } else {
            $result = $this->runUniqueJob($message->getMessageId(), $body['command'], $commandArguments);
        }

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param string $ownerId
     * @param string $commandName
     * @param array $commandArguments
     *
     * @return bool
     */
    private function runUniqueJob($ownerId, $commandName, array $commandArguments)
    {
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
            return $this->runCommand($commandName, $commandArguments);
        });
    }

    /**
     * @param string $jobId
     * @param string $commandName
     * @param array $commandArguments
     *
     * @return bool
     */
    private function runDelayedJob($jobId, $commandName, array $commandArguments)
    {
        $result = $this->jobRunner->runDelayed($jobId, function () use ($commandName, $commandArguments) {
            return $this->runCommand($commandName, $commandArguments);
        });

        return $result;
    }

    /**
     * @param string $commandName
     * @param array $commandArguments
     *
     * @return bool
     */
    private function runCommand($commandName, array $commandArguments)
    {
        $output = $this->commandRunner->run($commandName, $commandArguments);
        $this->logger->info(sprintf('Command %s was executed. Output: %s', $commandName, $output), [
            'command' => $commandName,
            'arguments' => $commandArguments,
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::RUN_COMMAND, Topics::RUN_COMMAND_DELAYED];
    }
}
