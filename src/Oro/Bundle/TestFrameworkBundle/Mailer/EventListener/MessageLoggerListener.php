<?php

declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Mailer\EventListener;

use Symfony\Component\Mailer\EventListener\MessageLoggerListener as SymfonyMessageLoggerListener;

/**
 * Extends {@see SymfonyMessageLoggerListener} to add an ability to ignore service reset.
 */
class MessageLoggerListener extends SymfonyMessageLoggerListener
{
    private bool $ignoreReset = false;

    public function setSkipReset(bool $ignoreReset): void
    {
        $this->ignoreReset = $ignoreReset;
    }

    public function reset(): void
    {
        if (!$this->ignoreReset) {
            parent::reset();
        }
    }
}
