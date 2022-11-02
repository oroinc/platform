<?php

namespace Oro\Bundle\MaintenanceBundle\Command;

use Oro\Bundle\MaintenanceBundle\Drivers\AbstractDriver;
use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Oro\Bundle\MaintenanceBundle\Event\MaintenanceEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Enables maintenance mode
 */
class MaintenanceLockCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:maintenance:lock';

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
            ->setDescription('Lock access to the site while maintenance...')
            ->setHelp(
                <<<'HELP'
    You can execute the lock without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>

    Or

    <info>%command.full_name% -n</info>
HELP
            )
            ->setAliases(['lexik:maintenance:lock']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $driver = $this->getDriver();

        $io = new SymfonyStyle($input, $output);

        if ($input->isInteractive()) {
            $io->caution('You are about to launch maintenance');

            $question = new ConfirmationQuestion('Are you sure you wish to continue?');
            if (!$io->askQuestion($question)) {
                $io->caution('Maintenance cancelled!');

                return 0;
            }
        }

        if ($driver->lock() === true) {
            $this->dispatcher->dispatch(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_ON);
            $io->success('Maintenance mode is turned on.');

            return 0;
        }

        $io->error('Failed to turn on maintenance mode.');

        return 1;
    }

    private function getDriver(): AbstractDriver
    {
        return $this->driverFactory->getDriver();
    }
}
