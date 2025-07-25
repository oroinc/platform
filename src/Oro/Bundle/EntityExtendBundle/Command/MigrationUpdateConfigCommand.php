<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigLogger;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsParser;
use Oro\Component\Log\OutputLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates extended entities configuration during the DB structure migration.
 * Entity config manager is set to utilizes only local cache.
 */
#[AsCommand(
    name: 'oro:entity-extend:migration:update-config',
    description: 'Updates extended entities configuration during the DB structure migration.',
    hidden: true
)]
class MigrationUpdateConfigCommand extends Command
{
    private ExtendOptionsParser $extendOptionsParser;
    private ExtendConfigProcessor $extendConfigProcessor;
    private ConfigManager $configManager;

    private string $optionsPath;

    public function __construct(
        ExtendOptionsParser $extendOptionsParser,
        ExtendConfigProcessor $extendConfigProcessor,
        ConfigManager $configManager,
        string $optionsPath
    ) {
        $this->extendOptionsParser = $extendOptionsParser;
        $this->extendConfigProcessor = $extendConfigProcessor;
        $this->configManager = $configManager;
        $this->optionsPath = $optionsPath;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    public function configure()
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Output modifications without applying them'
            )
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command updates extended entities configuration
during the database structure migration process.

  <info>php %command.full_name%</info>

<error>This is an internal command. Please do not run it manually.</error>

The <info>--dry-run</info> option outputs modifications without actually applying them.

  <info>php %command.full_name% --dry-run</info>

HELP
            )
            ->addUsage('--dry-run')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Update extended entities configuration');

        if (is_file($this->optionsPath)) {
            $this->configManager->useLocalCacheOnly();

            $options = unserialize(file_get_contents($this->optionsPath));

            $dryRun = $input->getOption('dry-run');
            $this->extendOptionsParser->setDryRunMode($dryRun);

            $options = $this->extendOptionsParser->parseOptions($options);

            $logger = new ConfigLogger(new OutputLogger($output));

            $this->extendConfigProcessor->processConfigs(
                $options,
                $logger,
                $dryRun
            );
        } else {
            $output->writeln(
                sprintf('<error>The options file "%s" was not found.</error>', $this->optionsPath)
            );

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
