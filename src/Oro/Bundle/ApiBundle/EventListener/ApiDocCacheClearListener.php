<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Oro\Bundle\ApiBundle\Collector\ApiDocWarningsCollector;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to API documentation cache clear command events and displays collected warnings.
 *
 * This listener starts collecting warnings when the oro:api:doc:cache:clear command begins
 * and displays a summary of all warnings when the command terminates.
 */
class ApiDocCacheClearListener implements EventSubscriberInterface
{
    private const COMMAND_NAME = 'oro:api:doc:cache:clear';

    public function __construct(private ApiDocWarningsCollector $collector)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onConsoleCommand',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate'
        ];
    }

    /**
     * Starts collecting warnings when the API doc cache clear command begins.
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        if ($this->isTargetCommand($event->getCommand()?->getName())) {
            $this->collector->startCollecting();
        }
    }

    /**
     * Stops collecting and displays warning summary when the command terminates.
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        if (!$this->isTargetCommand($event->getCommand()?->getName())) {
            return;
        }

        $this->collector->stopCollecting();
        $this->displayWarnings($event);
    }

    private function displayWarnings(ConsoleTerminateEvent $event): void
    {
        $warnings = $this->collector->getWarnings();
        if (empty($warnings)) {
            return;
        }

        $io = new SymfonyStyle($event->getInput(), $event->getOutput());
        $io->warning($this->formatWarningMessages($warnings));
    }

    /**
     * @param string[] $warnings
     * @return string[]
     */
    private function formatWarningMessages(array $warnings): array
    {
        $messages = ['API Documentation warnings found:'];

        foreach ($warnings as $warning) {
            $messages[] = '• ' . $warning;
        }

        $messages[] = '';
        $messages[] = sprintf('Total: %d warning(s)', count($warnings));

        return $messages;
    }

    private function isTargetCommand(?string $commandName): bool
    {
        return $commandName === self::COMMAND_NAME;
    }
}
