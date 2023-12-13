<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Stub;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Symfony\Component\Console\Command\Command;

class CronCommandStub extends Command implements CronCommandInterface
{
    private bool $active = true;

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '*/1 * * * *';
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }
}
