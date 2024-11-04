<?php

namespace Oro\Bundle\CronBundle\Async\Topic;

/**
 * Run a console command.
 */
class RunCommandDelayedTopic extends RunCommandTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.cron.run_command.delayed';
    }
}
