<?php

namespace Oro\Bundle\CronBundle\Async;

use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class CommandRunnerMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var CommandRunnerInterface
     */
    private $commandRunner;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CommandRunnerInterface $commandRunner
     * @param JobRunner $jobRunner
     * @param LoggerInterface $logger
     */
    public function __construct(
        CommandRunnerInterface $commandRunner,
        JobRunner $jobRunner,
        LoggerInterface $logger
    ) {
        $this->commandRunner = $commandRunner;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());

        if (!isset($body['command'])) {
            $this->logger->critical(
                'Got invalid message: empty command',
                ['message' => $message]
            );

            return self::REJECT;
        }

        $commandArguments = isset($body['arguments']) ? $body['arguments'] : [];

        $jobName = 'oro:cron:run_command:' . $body['command'];

        if ($commandArguments) {
            $jobName .= '-'. implode('-', $commandArguments);
        }

        $result = $this->jobRunner->runUnique(
            $message->getMessageId(),
            $jobName,
            function () use ($body, $commandArguments) {
                $output = $this->commandRunner->run($body['command'], $commandArguments);

                $this->logger->info(
                    sprintf(
                        'Ran command %s with arguments: %s. Got output %s',
                        $body['command'],
                        implode(' ', $commandArguments),
                        $output
                    ),
                    [
                        'command' => $body['command'],
                        'arguments' => $commandArguments,
                        'output' => $output
                    ]
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
        return [Topics::RUN_COMMAND];
    }
}
