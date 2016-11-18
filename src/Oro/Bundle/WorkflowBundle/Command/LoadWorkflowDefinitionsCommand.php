<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowDefinitionHandler;

class LoadWorkflowDefinitionsCommand extends ContainerAwareCommand
{
    const NAME = 'oro:workflow:definitions:load';
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
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
            )
            ->addOption(
                'skip-scope-processing',
                null,
                InputOption::VALUE_NONE,
                'Skip updating WorkflowScope entities for existing WorkflowDefinition entities'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $usedDirectories = $input->getOption('directories');
        $usedDirectories = $usedDirectories ?: null;

        $usedWorkflows = $input->getOption('workflows');
        $usedWorkflows = $usedWorkflows ?: null;

        $container = $this->getContainer();

        /** @var WorkflowConfigurationProvider $configurationProvider */
        $configurationProvider = $container->get('oro_workflow.configuration.provider.workflow_config');
        $workflowConfiguration = $configurationProvider->getWorkflowDefinitionConfiguration(
            $usedDirectories,
            $usedWorkflows
        );
        /** @var WorkflowDefinitionHandler $definitionHandler */
        $definitionHandler = $container->get('oro_workflow.handler.workflow_definition');

        if ($workflowConfiguration) {
            $output->writeln('Loading workflow definitions...');

            /** @var WorkflowDefinitionConfigurationBuilder $configurationBuilder */
            $configurationBuilder = $container->get('oro_workflow.configuration.builder.workflow_definition');
            $workflowDefinitionRepository = $container->get('oro_entity.doctrine_helper')
                ->getEntityRepository(WorkflowDefinition::class);
            $workflowDefinitions = $configurationBuilder->buildFromConfiguration($workflowConfiguration);

            $this->setWorkflowScopeManagerEnabled(!$input->getOption('skip-scope-processing'));

            foreach ($workflowDefinitions as $workflowDefinition) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $workflowDefinition->getName()));

                // all loaded workflows set as system by default
                $workflowDefinition->setSystem(true);

                $existingWorkflowDefinition = $workflowDefinitionRepository->find($workflowDefinition->getName());

                if ($existingWorkflowDefinition) {
                    $definitionHandler->updateWorkflowDefinition($existingWorkflowDefinition, $workflowDefinition);
                } else {
                    $definitionHandler->createWorkflowDefinition($workflowDefinition);
                }

                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln(Yaml::dump($workflowDefinition->getConfiguration(), 10));
                }
            }

            $this->setWorkflowScopeManagerEnabled();
        } else {
            $output->writeln('No workflow definitions found.');
        }
    }

    /**
     * @param bool $isEnabled
     */
    protected function setWorkflowScopeManagerEnabled($isEnabled = true)
    {
        $this->getContainer()->get('oro_workflow.manager.workflow_scope')->setEnabled($isEnabled);
    }
}
