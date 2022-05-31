<?php

namespace Oro\Bundle\PlatformBundle\EventListener\Console;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Output warning message when xDebug is enabled on command execution.
 */
class XdebugListener
{
    private array $supportedCommands = [];

    public function __construct(array $supportedCommands = [])
    {
        foreach ($supportedCommands as $command) {
            $this->registerCommand($command);
        }
    }

    public function registerCommand(string $commandName): void
    {
        $this->supportedCommands[$commandName] = true;
    }

    public function onCommandExecute(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command || empty($this->supportedCommands[$command->getName()])) {
            return;
        }

        $this->checkActiveXdebug($event->getInput(), $event->getOutput());
    }

    private function checkActiveXdebug(InputInterface $input, OutputInterface $output): void
    {
        if (extension_loaded('xdebug')) {
            $io = new SymfonyStyle($input, $output);
            $io->warning([
                'The xDebug PHP extension is enabled.',
                'This may slow down your application even when the extension is not in use.' . PHP_EOL .
                'Where possible, consider disabling it to speed up the application performance.'
            ]);
        }
    }
}
