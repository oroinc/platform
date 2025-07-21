<?php

declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Mailer\EventListener;

use Symfony\Component\Mailer\EventListener\MessageLoggerListener as SymfonyMessageLoggerListener;

/**
 * Extends {@see SymfonyMessageLoggerListener} to add an ability to ignore service reset.
 */
class MessageLoggerListener extends SymfonyMessageLoggerListener
{
    private static ?MessageLoggerListener $instance = null;

    private bool $ignoreReset = false;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function setSkipReset(bool $ignoreReset): void
    {
        $this->ignoreReset = $ignoreReset;
    }

    #[\Override]
    public function reset(): void
    {
        if (!$this->ignoreReset) {
            parent::reset();
        }
    }
}
