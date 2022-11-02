<?php

namespace Oro\Bundle\MaintenanceBundle\Command;

use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Oro\Bundle\MaintenanceBundle\Event\MaintenanceEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Disables maintenance mode
 */
class MaintenanceUnlockCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:maintenance:unlock';

    private DriverFactory $driverFactory;

    private EventDispatcherInterface $dispatcher;

    public function __construct(DriverFactory $driverFactory, EventDispatcherInterface $dispatcher)
    {
        $this->driverFactory = $driverFactory;
        $this->dispatcher = $dispatcher;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Unlock access to the site while maintenance...')
            ->setHelp(
                <<<'HELP'
    You can execute the unlock without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>
HELP
            )
            ->setAliases(['lexik:maintenance:unlock']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->confirmUnlock($input, $io)) {
            return 0;
        }

        $driver = $this->driverFactory->getDriver();

        if ($driver->unlock() === true) {
            $this->dispatcher->dispatch(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_OFF);
            $io->success('Maintenance mode is turned off.');

            return 0;
        }

        $io->error('Failed to turn off maintenance mode.');

        return 1;
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
