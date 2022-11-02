<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Stub;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Symfony\Component\Console\Command\Command;

class CronCommandStub extends Command implements CronCommandScheduleDefinitionInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '*/1 * * * *';
    }
}
