<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Command;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigLoader;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigLogger;
use Oro\Component\Log\OutputLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates configuration data for entities.
 */
class UpdateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:entity-config:update';

    private ConfigLoader $configLoader;

    public function __construct(ConfigLoader $configLoader)
    {
        parent::__construct();

        $this->configLoader = $configLoader;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function configure()
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite config option values')
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Regular expression to filter entities by their class name'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Output modifications without applying them'
            )
            ->setDescription('Updates configuration data for entities.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command parses tracking logs.

  <info>php %command.full_name%</info>

The <info>--force</info> option can be used to force overriding of config option values.

  <info>php %command.full_name% --force</info>

The <info>--dry-run</info> option outputs modifications without actually applying them.

  <info>php %command.full_name% --dry-run</info>

A regular expression provided with the <info>--filter</info> option will be used to filter entities
by their class names:

  <info>php %command.full_name% --filter=<regexp></info>
  <info>php %command.full_name% --filter='Oro\\Bundle\\User*'</info>
  <info>php %command.full_name% --filter='^Oro\\(.*)\\Region$'</info>

HELP
            )
            ->addUsage('--force')
            ->addUsage('--dry-run')
            ->addUsage('--filter=<regexp>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Update configuration data for entities');

        $filter = $input->getOption('filter');
        if ($filter) {
            $filter = function ($doctrineAllMetadata) use ($filter) {
                return array_filter(
                    $doctrineAllMetadata,
                    function ($item) use ($filter) {
                        /** @var ClassMetadataInfo $item */
                        return preg_match('/' . str_replace('\\', '\\\\', $filter) . '/', $item->getName());
                    }
                );
            };
        }

        $verbosity = $output->getVerbosity();
        if (!$input->getOption('dry-run')) {
            $verbosity--;
        }
        $logger = new ConfigLogger(new OutputLogger($output, true, $verbosity));
        /** @var ConfigLoader $loader */
        $this->configLoader->load(
            $input->getOption('force'),
            $filter,
            $logger,
            $input->getOption('dry-run')
        );

        return 0;
    }
}
