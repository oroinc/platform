<?php

namespace Oro\Bundle\CronBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Run a console command.
 */
class RunCommandTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'oro.cron.run_command';
    }

    public static function getDescription(): string
    {
        return 'Run a console command';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'command',
                'arguments',
                'jobId',
            ])
            ->setRequired([
                'command',
            ])
            ->setDefault('arguments', [])
            ->addAllowedTypes('command', 'string')
            ->addAllowedTypes('arguments', 'array')
            ->addAllowedTypes('jobId', 'int');
    }

    public function createJobName($messageBody): string
    {
        $commandArguments = $messageBody['arguments'];
        $commandName = $messageBody['command'];

        $jobName = sprintf('oro:cron:run_command:%s', $commandName);
        if ($commandArguments) {
            array_walk($commandArguments, static function ($item, $key) use (&$jobName) {
                if (is_array($item)) {
                    $item = implode(',', $item);
                }

                $jobName .= sprintf('-%s=%s', $key, $item);
            });
        }

        return $jobName;
    }
}
