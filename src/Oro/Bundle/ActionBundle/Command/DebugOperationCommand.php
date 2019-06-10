<?php

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
 * The CLI command to debug configuration of actions.
 */
class DebugOperationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:debug:operation';

    /** @var ConfigurationProviderInterface */
    private $operationsProvider;

    /** @var OperationRegistry */
    private $operationRegistry;

    /** @var ConfigurationProviderInterface */
    private $actionGroupsProvider;

    /** @var ActionGroupRegistry */
    private $actionGroupRegistry;

    /**
     * @param ConfigurationProviderInterface $operationsProvider
     * @param OperationRegistry $operationRegistry
     * @param ConfigurationProviderInterface $actionGroupsProvider
     * @param ActionGroupRegistry $actionGroupRegistry
     */
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Debug operation configuration')
            ->addArgument('name', InputArgument::OPTIONAL, 'Names of the name of node that should be dumped')
            ->addOption('action-group', null, InputOption::VALUE_NONE, 'Debug action_group')
            ->addOption('assemble', null, InputOption::VALUE_NONE, 'Assemble configuration');
    }

    /**
     * {@inheritdoc}
     */
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
    }
}
