<?php
declare(strict_types=1);

namespace Oro\Bundle\CronBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Engine\CommandRunnerInterface;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\CronBundle\Helper\CronHelper;
use Oro\Bundle\CronBundle\Tools\CommandRunner;
use Oro\Bundle\PlatformBundle\Maintenance\Mode;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Launches scheduled cron commands.
 */
class CronCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:cron';

    private ManagerRegistry $registry;
    private Mode $maintenanceMode;
    private CronHelper $cronHelper;
    private CommandRunnerInterface $commandRunner;
    private LoggerInterface $logger;

    private string $environment;

    public function __construct(
        ManagerRegistry $registry,
        Mode $maintenanceMode,
        CronHelper $cronHelper,
        CommandRunnerInterface $commandRunner,
        LoggerInterface $logger,
        string $environment
    ) {
        parent::__construct();

        $this->registry = $registry;
        $this->maintenanceMode = $maintenanceMode;
        $this->cronHelper = $cronHelper;
        $this->commandRunner = $commandRunner;
        $this->logger = $logger;
        $this->environment = $environment;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Launches scheduled cron commands.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command launches scheduled cron commands that are due for execution.

This launcher only schedules the actual command executions by adding messages to the message queue
and the commands are executed asynchronously, so ensure that the message consumer processes
(<info>oro:message-queue:consume</info>) are running for the scheduled commands to be executed in time.

The commands implementing <info>\Oro\Bundle\CronBundle\Command\SynchronousCommandInterface</info>
are an exception to this rule as they are launched immediately.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // check for maintenance mode - do not run cron jobs if it is switched on
        if ($this->maintenanceMode->isOn()) {
            $message = 'System is in maintenance mode, aborting';
            $output->writeln('');
            $output->writeln(sprintf('<error>%s</error>', $message));
            $this->logger->error($message);
            return;
        }

        $schedules = $this->getAllSchedules();

        /** @var Schedule $schedule */
        foreach ($schedules as $schedule) {
            $cronExpression = $this->cronHelper->createCron($schedule->getDefinition());
            if ($cronExpression->isDue()) {
                /** @var CronCommandInterface $command */
                $command = $this->getApplication()->get($schedule->getCommand());

                if ($command instanceof CronCommandInterface && !$command->isActive()) {
                    $output->writeln(
                        'Skipping not enabled command ' . $schedule->getCommand(),
                        OutputInterface::VERBOSITY_DEBUG
                    );
                    continue;
                }

                // in case of synchronous cron command - run it in separate process
                if ($command instanceof SynchronousCommandInterface) {
                    $output->writeln(
                        'Running synchronous command ' . $schedule->getCommand(),
                        OutputInterface::VERBOSITY_DEBUG
                    );
                    CommandRunner::runCommand(
                        $schedule->getCommand(),
                        array_merge(
                            $schedule->getArguments(),
                            ['--env' => $this->environment]
                        )
                    );
                } else {
                    // in case of common cron command - send the MQ message that will run this command
                    $output->writeln(
                        'Scheduling run for command ' . $schedule->getCommand(),
                        OutputInterface::VERBOSITY_DEBUG
                    );
                    $this->commandRunner->run(
                        $schedule->getCommand(),
                        $this->resolveOptions($schedule->getArguments())
                    );
                }
            } else {
                $output->writeln('Skipping not due command '.$schedule->getCommand(), OutputInterface::VERBOSITY_DEBUG);
            }
        }

        $output->writeln('All commands scheduled', OutputInterface::VERBOSITY_DEBUG);
    }

    /**
     * Convert command arguments to options. It needed for correctly pass this arguments into ArrayInput:
     * new ArrayInput(['name' => 'foo', '--bar' => 'foobar']);
     */
    protected function resolveOptions(array $commandOptions): array
    {
        $options = [];
        foreach ($commandOptions as $key => $option) {
            $params = explode('=', $option, 2);
            if (is_array($params) && count($params) === 2) {
                $options[$params[0]] = $params[1];
            } else {
                $options[$key] = $option;
            }
        }
        return $options;
    }

    /**
     * @return ArrayCollection|Schedule[]
     */
    private function getAllSchedules()
    {
        return new ArrayCollection($this->registry->getRepository('OroCronBundle:Schedule')->findAll());
    }
}
