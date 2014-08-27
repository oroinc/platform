<?php

namespace Oro\Bundle\TestFrameworkBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\KernelInterface;

use Oro\Bundle\MigrationBundle\Command\LoadMigrationsCommand;
use Oro\Bundle\InstallerBundle\CommandExecutor;

class UpdateSchemaListener
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();
        $environment = $this->kernel->getEnvironment();

        if ($environment == 'test'
            && $command instanceof LoadMigrationsCommand
            && $event->getInput()->getOption('force')
        ) {
            $executor = new CommandExecutor(
                $environment,
                $event->getOutput(),
                $command->getApplication()
            );
            $executor->runCommand('oro:test:schema:update');
        }
    }
}
