<?php

namespace Oro\Bundle\MaintenanceBundle\Command;

use Oro\Bundle\MaintenanceBundle\Drivers\AbstractDriver;
use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Oro\Bundle\MaintenanceBundle\Drivers\DriverTtlInterface;
use Oro\Bundle\MaintenanceBundle\Event\MaintenanceEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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

    protected int $ttl = 0;

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
            ->addArgument(
                'ttl',
                InputArgument::OPTIONAL,
                'Specifies the time in seconds to enable maintenance lock.',
            )
            ->setHelp(
                <<<'HELP'
    You can optionally set a time to live of the maintenance

    <info>%command.full_name% 3600</info>

    You can execute the lock without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>

    Or

    <info>%command.full_name% 3600 -n</info>
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

        if (!$this->setTtl($input, $output, $driver)) {
            return 1;
        }

        $lockStatus = $driver->lock();
        $message = $driver->getMessageLock($lockStatus);
        if ($lockStatus === true) {
            $this->dispatcher->dispatch(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_ON);
            $io->success($message);

            return 0;
        }

        $io->error($message);

        return 1;
    }

    private function setTtl(InputInterface $input, OutputInterface $output, AbstractDriver $driver): bool
    {
        $ttlArgument = $input->getArgument('ttl');
        if (null !== $ttlArgument) {
            if (is_numeric($ttlArgument)) {
                $this->ttl = (int)$ttlArgument;
            } else {
                $io = new SymfonyStyle($input, $output);
                $io->error('Time to live must be an integer');

                return false;
            }
        } elseif ($driver instanceof DriverTtlInterface) {
            $this->ttl = $driver->getTtl();
        }

        // Sets ttl if the driver supports it.
        if ($driver instanceof DriverTtlInterface) {
            $driver->setTtl($this->ttl);
        }

        return true;
    }

    private function getDriver(): AbstractDriver
    {
        return $this->driverFactory->getDriver();
    }
}
