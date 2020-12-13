<?php
declare(strict_types=1);

namespace Oro\Bundle\WorkflowBundle\Command;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowDefinitionHandler;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads workflow definitions to the database.
 */
class LoadWorkflowDefinitionsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:workflow:definitions:load';

    private WorkflowConfigurationProvider $configurationProvider;
    private WorkflowDefinitionHandler $definitionHandler;
    private WorkflowDefinitionConfigurationBuilder $configurationBuilder;
    private ManagerRegistry $registry;

    public function __construct(
        WorkflowConfigurationProvider $configurationProvider,
        WorkflowDefinitionHandler $definitionHandler,
        WorkflowDefinitionConfigurationBuilder $configurationBuilder,
        ManagerRegistry $registry
    ) {
        parent::__construct();

        $this->configurationProvider = $configurationProvider;
        $this->definitionHandler = $definitionHandler;
        $this->configurationBuilder = $configurationBuilder;
        $this->registry = $registry;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption(
                'directories',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Directories with workflow configurations'
            )
            ->addOption(
                'workflows',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Workflow names'
            )
            ->setDescription('Loads workflow definitions to the database.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command loads workflow definitions
from configuration files to the database.

  <info>php %command.full_name%</info>

The <info>--directories</info> option can be used to specify custom location(s)
of the workflow configuration files:

  <info>php %command.full_name% --directories=<path1> --directories=<path2></info>

The <info>--workflows</info> option can be used to load only the specified workflows:

  <info>php %command.full_name% --workflows=<workflow1> --workflows=<workflow2></info>

HELP
            )
            ->addUsage('--directories=<path1> --directories=<path2>')
            ->addUsage('--workflows=<workflow1> --workflows=<workflow2>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $usedDirectories = $input->getOption('directories') ?: null;
        $usedWorkflows = $input->getOption('workflows') ?: null;

        $workflowConfiguration = $this->configurationProvider->getWorkflowDefinitionConfiguration(
            $usedDirectories,
            $usedWorkflows
        );

        if ($workflowConfiguration) {
            $output->writeln('Loading workflow definitions...');

            /** @var WorkflowDefinitionRepository $workflowDefinitionRepository */
            $workflowDefinitionRepository = $this->registry->getRepository(WorkflowDefinition::class);
            $workflowDefinitions = $this->configurationBuilder->buildFromConfiguration($workflowConfiguration);

            foreach ($workflowDefinitions as $workflowDefinition) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $workflowDefinition->getName()));

                // all loaded workflows set as system by default
                $workflowDefinition->setSystem(true);

                /** @var WorkflowDefinition $existingWorkflowDefinition */
                $existingWorkflowDefinition = $workflowDefinitionRepository->find($workflowDefinition->getName());

                if ($existingWorkflowDefinition) {
                    $this->definitionHandler->updateWorkflowDefinition(
                        $existingWorkflowDefinition,
                        $workflowDefinition
                    );
                } else {
                    $this->definitionHandler->createWorkflowDefinition($workflowDefinition);
                }

                $output->writeln(
                    Yaml::dump($workflowDefinition->getConfiguration(), 10),
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }
            $output->writeln('');
            $output->writeln('Please run command \'<info>oro:translation:load</info>\' to load translations.');
        } else {
            $output->writeln('No workflow definitions found.');
        }
    }
}
