<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Stub;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'oro:cron:test:usual')]
class TestCronCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '0 0 * * *';
    }
}
