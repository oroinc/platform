<?php

namespace Oro\Bundle\MaintenanceBundle\Command;

use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Oro\Bundle\MaintenanceBundle\Event\MaintenanceEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Disables maintenance mode
 */
#[AsCommand(
    name: 'oro:maintenance:unlock',
    description: 'Unlock access to the site while maintenance...',
    aliases: ['lexik:maintenance:unlock']
)]
class MaintenanceUnlockCommand extends Command
{
    private DriverFactory $driverFactory;

    private EventDispatcherInterface $dispatcher;

    public function __construct(DriverFactory $driverFactory, EventDispatcherInterface $dispatcher)
    {
        $this->driverFactory = $driverFactory;
        $this->dispatcher = $dispatcher;

        parent::__construct();
    }

    #[\Override]
    protected function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
    You can execute the unlock without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>
HELP
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->confirmUnlock($input, $io)) {
            return Command::SUCCESS;
        }

        $driver = $this->driverFactory->getDriver();

        if (!$driver->isExists()) {
            $io->note('Maintenance mode is already disabled.');
            return Command::SUCCESS;
        }

        if ($driver->unlock() === true) {
            $this->dispatcher->dispatch(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_OFF);
            $io->success('Maintenance mode is turned off.');

            return Command::SUCCESS;
        }

        $io->error('Failed to turn off maintenance mode.');

        return Command::FAILURE;
    }

    protected function confirmUnlock(InputInterface $input, SymfonyStyle $io): bool
    {
        if (!$input->isInteractive()) {
            $confirmation = true;
        } else {
            $io->caution('You are about to unlock your server.');

            $confirmation = $io->askQuestion(
                new ConfirmationQuestion('WARNING! Are you sure you wish to continue? (y/n)')
            );
        }

        if (!$confirmation) {
            $io->caution('Action cancelled!');
        }

        return $confirmation;
    }
}
