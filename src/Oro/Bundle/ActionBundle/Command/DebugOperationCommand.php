<?php
declare(strict_types=1);

namespace Oro\Bundle\ActionBundle\Command;

use Doctrine\Common\Util\Debug;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Displays available operations and action groups.
 */
class DebugOperationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:debug:operation';

    private ConfigurationProviderInterface $operationsProvider;
    private OperationRegistry $operationRegistry;
    private ConfigurationProviderInterface $actionGroupsProvider;
    private ActionGroupRegistry $actionGroupRegistry;

    public function __construct(
        ConfigurationProviderInterface $operationsProvider,
        OperationRegistry $operationRegistry,
        ConfigurationProviderInterface $actionGroupsProvider,
        ActionGroupRegistry $actionGroupRegistry
    ) {
        parent::__construct();

        $this->operationsProvider = $operationsProvider;
        $this->operationRegistry = $operationRegistry;
        $this->actionGroupsProvider = $actionGroupsProvider;
        $this->actionGroupRegistry = $actionGroupRegistry;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Operation or action group')
            ->addOption('action-group', null, InputOption::VALUE_NONE, 'Show action groups instead of operations')
            ->addOption('assemble', null, InputOption::VALUE_NONE, 'Show instantiated objects')
            ->setDescription('Displays available operations and action groups.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command displays available operations by default:

  <info>php %command.full_name%</info>

Use the <info>--action-group</info> option to see action groups instead of operations:

  <info>php %command.full_name% --action-group</info>

To get information about a specific operation or action group, specify its name:

  <info>php %command.full_name% <operation-name></info>
  <info>php %command.full_name% --action-group <action-group-name></info>
  <info>php %command.full_name% DELETE</info>
  <info>php %command.full_name% --action-group DELETE</info>

The <info>--assemble</info> option can be used to display instantiated objects instead of plain data:

  <info>php %command.full_name% --assemble <operation-name></info>
  <info>php %command.full_name% --assemble --action-group <action-group-name></info>
  <info>php %command.full_name% --assemble DELETE</info>
  <info>php %command.full_name% --assemble --action-group DELETE</info>

HELP
            )
            ->addUsage('--action-group')
            ->addUsage('--action-group <name>')
            ->addUsage('--assemble --action-group')
            ->addUsage('--assemble --action-group <name>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('action-group')) {
            $output->writeln('Load action_groups ...');
            $provider = $this->actionGroupsProvider;
        } else {
            $output->writeln('Load operations ...');
            $provider = $this->operationsProvider;
        }

        $configuration = $provider->getConfiguration();

        if ($input->getOption('assemble')) {
            if ($input->getOption('action-group')) {
                $registry = $this->actionGroupRegistry;
            } else {
                $registry = $this->operationRegistry;
            }

            foreach ($configuration as $name => &$value) {
                $value = $registry->findByName($name)->getDefinition();
            }
        }

        if ($configuration) {
            $name = $input->getArgument('name');

            if ($name && isset($configuration[$name])) {
                $output->writeln($name);
                Debug::dump($configuration[$name], 100);
            } else {
                foreach (array_keys($configuration) as $key) {
                    $output->writeln($key);
                }
            }
        } else {
            $output->writeln('No actions found.');
        }

        return 0;
    }
}
