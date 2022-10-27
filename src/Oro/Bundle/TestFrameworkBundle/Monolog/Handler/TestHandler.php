<?php

declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Monolog\Handler;

use Monolog\Handler\TestHandler as SymfonyTestHandler;

/**
 * Extends {@see SymfonyTestHandler} to add an ability to ignore logs clearing.
 */
class TestHandler extends SymfonyTestHandler
{
    private bool $skipReset = false;

    public function setSkipReset(bool $skipReset): void
    {
        $this->skipReset = $skipReset;

        parent::setSkipReset($skipReset);
    }

    public function clear(): void
    {
        if (!$this->skipReset) {
            parent::clear();
        }
    }
}
