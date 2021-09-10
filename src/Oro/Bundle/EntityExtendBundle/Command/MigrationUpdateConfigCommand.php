<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Command;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigLogger;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendConfigProcessor;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsParser;
use Oro\Component\Log\OutputLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates extended entities configuration during the DB structure migration.
 */
class MigrationUpdateConfigCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity-extend:migration:update-config';

    private ExtendOptionsParser $extendOptionsParser;
    private ExtendConfigProcessor $extendConfigProcessor;

    private string $optionsPath;

    public function __construct(
        ExtendOptionsParser $extendOptionsParser,
        ExtendConfigProcessor $extendConfigProcessor,
        string $optionsPath
    ) {
        $this->extendOptionsParser = $extendOptionsParser;
        $this->extendConfigProcessor = $extendConfigProcessor;
        $this->optionsPath = $optionsPath;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Output modifications without applying them'
            )
            ->setHidden(true)
            ->setDescription('Updates extended entities configuration during the DB structure migration.')
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
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Update extended entities configuration');

        if (is_file($this->optionsPath)) {
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

            return 1;
        }

        return 0;
    }
}
