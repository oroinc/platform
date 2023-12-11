<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Stub;

use Symfony\Component\Console\Command\Command;

class NoScheduleDefinitionTestCronCommand extends Command
{
    protected static $defaultName = 'oro:cron:test:no_schedule_definition';
}
