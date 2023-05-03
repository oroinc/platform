<?php

namespace Oro\Bundle\CronBundle\Async;

use Oro\Bundle\CronBundle\Async\Topic\RunCommandDelayedTopic;
use Oro\Bundle\CronBundle\Async\Topic\RunCommandTopic;
use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
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

    private JobRunner $jobRunner;

    private CommandRunnerInterface $commandRunner;

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
        $messageBody = $message->getBody();

        $commandArguments = $messageBody['arguments'];
        $commandName = $messageBody['command'];

        if (array_key_exists('jobId', $messageBody)) {
            $result = $this->jobRunner->runDelayed(
                $messageBody['jobId'],
                function () use ($commandName, $commandArguments) {
                    return $this->runCommand($commandName, $commandArguments);
                }
            );
        } else {
            $result = $this->jobRunner->runUniqueByMessage(
                $message,
                function () use ($commandName, $commandArguments) {
                    return $this->runCommand($commandName, $commandArguments);
                }
            );
        }

        return $result ? self::ACK : self::REJECT;
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
        return [RunCommandTopic::getName(), RunCommandDelayedTopic::getName()];
    }
}
