<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation\EventListener;

use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Oro\Bundle\TestFrameworkBundle\Behat\Session\Mink\WatchModeSessionHolder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Watch mode test input/output isolation.
 */
readonly class WatchModeIsolationSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly WatchModeSessionHolder $sessionHolder)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ExerciseCompleted::AFTER => ['afterExercise', -150],
        ];
    }

    public function afterExercise(ExerciseCompleted $event): void
    {
        // fixes an issue with incorrect console output after interrupting execution (Ctrl+c) in --watch mode
        if ($this->sessionHolder->isWatchMode() || $this->sessionHolder->isWatchFrom()) {
            shell_exec('stty sane'); // restores the terminal settings to a default state.
        }
    }
}
