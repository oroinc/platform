<?php
declare(strict_types=1);

namespace Oro\Bundle\MigrationBundle\Command;

use Oro\Bundle\MigrationBundle\Migration\Loader\MigrationsLoader;
use Oro\Bundle\MigrationBundle\Migration\MigrationExecutorWithNameGenerator;
use Oro\Component\Log\OutputLogger;
use Oro\Component\PhpUtils\Tools\CommandExecutor\CommandExecutor;
use Oro\Component\PhpUtils\Tools\CommandExecutor\CommandExecutorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Executes migration scripts.
 */
class LoadMigrationsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:migration:load';

    private MigrationsLoader $migrationLoader;
    private MigrationExecutorWithNameGenerator $migrationExecutor;
    private CommandExecutorInterface $commandExecutor;

    public function __construct(
        MigrationsLoader $migrationLoader,
        MigrationExecutorWithNameGenerator $migrationExecutor,
        CommandExecutorInterface $commandExecutor
    ) {
        $this->migrationLoader = $migrationLoader;
        $this->migrationExecutor = $migrationExecutor;
        $this->commandExecutor = $commandExecutor;
        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force the execution')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'List migrations without applying them')
            ->addOption('show-queries', null, InputOption::VALUE_NONE, 'Display database queries for each migration')
            ->addOption(
                'bundles',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Bundles to load the migrations from'
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Bundles that should be skipped'
            )
            ->addOption(
                'timeout',
                null,
                InputOption::VALUE_OPTIONAL,
                'Timeout for child command execution',
                CommandExecutor::DEFAULT_TIMEOUT
            )
            ->setDescription('Executes migration scripts.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command executes migration scripts.

  <info>php %command.full_name%</info>

The <info>--force</info> option is just a safety switch. The command will exit
if this option is not used.

  <info>php %command.full_name% --force</info>

The <info>--dry-run</info> option can be used to list the migrations without applying them:

  <info>php %command.full_name% --dry-run</info>

The <info>--show-queries</info> option will display the database queries executed for each migration:

  <info>php %command.full_name% --show-queries</info>

The <info>--bundles</info> option can be used to load migrations only from the specified bundles:

  <info>php %command.full_name% --bundles=<bundle1> --bundles=<bundle2> --bundles=<bundleN></info>

The <info>--exclude</info> option can be used to skip loading migrations from the specified bundles:

  <info>php %command.full_name% --exclude=<bundle1> --exclude=<bundle2> --exclude=<bundleN></info>

The <info>--timeout</info> option can be used to limit execution time of the child commands:

  <info>php %command.full_name% --timeout=<seconds></info>

HELP
            )
            ->addUsage('--force ')
            ->addUsage('--dry-run')
            ->addUsage('--show-queries')
            ->addUsage('--bundles=<bundle1> --bundles=<bundle2> --bundles=<bundleN>')
            ->addUsage('--exclude=<bundle1> --exclude=<bundle2> --exclude=<bundleN>')
            ->addUsage('--timeout=<seconds>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        $dryRun = $input->getOption('dry-run');
        $this->initCommandExecutor($input);

        if ($force || $dryRun) {
            $output->writeln($dryRun ? 'List of migrations:' : 'Process migrations...');

            $migrationLoader = $this->getMigrationLoader($input);
            $migrations      = $migrationLoader->getMigrations();
            if (!empty($migrations)) {
                if ($input->getOption('dry-run') && !$input->getOption('show-queries')) {
                    foreach ($migrations as $item) {
                        $output->writeln(sprintf('  <comment>> %s</comment>', get_class($item->getMigration())));
                    }
                } else {
                    $logger      = new OutputLogger($output, true, null, '  ');
                    $queryLogger = new OutputLogger(
                        $output,
                        true,
                        $input->getOption('show-queries') ? null : OutputInterface::VERBOSITY_QUIET,
                        '    '
                    );

                    $this->migrationExecutor->setLogger($logger);
                    $this->migrationExecutor->getQueryExecutor()->setLogger($queryLogger);
                    $this->migrationExecutor->executeUp($migrations, $input->getOption('dry-run'));
                }
            }
        } else {
            $output->writeln(
                '<comment>ATTENTION</comment>: Database backup is highly recommended before executing this command.'
            );
            $output->writeln('');
            $output->writeln('To force execution run command with <info>--force</info> option:');
            $output->writeln(sprintf('    <info>%s --force</info>', $this->getName()));

            return 1;
        }

        return 0;
    }

    protected function getMigrationLoader(InputInterface $input): MigrationsLoader
    {
        $bundles         = $input->getOption('bundles');
        if (!empty($bundles)) {
            $this->migrationLoader->setBundles($bundles);
        }
        $excludeBundles = $input->getOption('exclude');
        if (!empty($excludeBundles)) {
            $this->migrationLoader->setExcludeBundles($excludeBundles);
        }

        return $this->migrationLoader;
    }

    protected function initCommandExecutor(InputInterface $input): void
    {
        $timeout = $input->getOption('timeout');
        if ($timeout >= 0) {
            $this->commandExecutor->setDefaultOption('process-timeout', $timeout);
        }
        if (true === $input->getOption('no-debug')) {
            $this->commandExecutor->setDefaultOption('no-debug');
        }
    }
}
