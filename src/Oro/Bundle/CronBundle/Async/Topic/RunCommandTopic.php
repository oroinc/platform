<?php

namespace Oro\Bundle\CronBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Run a console command.
 */
class RunCommandTopic extends AbstractTopic
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
}
