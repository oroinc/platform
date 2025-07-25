<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Stub;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'oro:cron:test:no_schedule_definition')]
class NoScheduleDefinitionTestCronCommand extends Command
{
}
