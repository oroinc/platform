<?php

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
 * Load workflow definitions from configuration files to the database
 */
class LoadWorkflowDefinitionsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:workflow:definitions:load';

    /** @var WorkflowConfigurationProvider */
    private $configurationProvider;

    /** @var WorkflowDefinitionHandler */
    private $definitionHandler;

    /** @var WorkflowDefinitionConfigurationBuilder */
    private $configurationBuilder;

    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param WorkflowConfigurationProvider $configurationProvider
     * @param WorkflowDefinitionHandler $definitionHandler
     * @param WorkflowDefinitionConfigurationBuilder $configurationBuilder
     * @param ManagerRegistry $registry
     */
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Load workflow definitions from configuration files to the database')
            ->addOption(
                'directories',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Directories used to find configuration files'
            )
            ->addOption(
                'workflows',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Names of the workflow definitions that should be loaded'
            );
    }

    /**
     * {@inheritdoc}
     */
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
