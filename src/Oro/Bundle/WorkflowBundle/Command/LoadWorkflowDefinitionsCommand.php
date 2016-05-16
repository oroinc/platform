<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Component\Yaml\Yaml;

class LoadWorkflowDefinitionsCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:workflow:definitions:load')
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
     * @inheritdoc
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

        if ($workflowConfiguration) {
            $output->writeln('Loading workflow definitions...');

            /** @var EntityManager $manager */
            $manager = $container->get('doctrine.orm.default_entity_manager');

            /** @var WorkflowDefinitionConfigurationBuilder $configurationBuilder */
            $configurationBuilder = $container->get('oro_workflow.configuration.builder.workflow_definition');
            $workflowDefinitions = $configurationBuilder->buildFromConfiguration($workflowConfiguration);

            $workflowDefinitionRepository = $manager->getRepository('OroWorkflowBundle:WorkflowDefinition');
            foreach ($workflowDefinitions as $workflowDefinition) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $workflowDefinition->getName()));

                // all loaded workflows set as system by default
                $workflowDefinition->setSystem(true);

                /** @var WorkflowDefinition $existingWorkflowDefinition */
                $existingWorkflowDefinition = $workflowDefinitionRepository->find($workflowDefinition->getName());

                // workflow definition should be overridden if workflow definition with such name already exists
                if ($existingWorkflowDefinition) {
                    $existingWorkflowDefinition->import($workflowDefinition);
                } else {
                    $manager->persist($workflowDefinition);
                }

                if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
                    $output->writeln(
                        Yaml::dump($workflowDefinition->getConfiguration(), 6, 4)
                    );
                }
            }

            $manager->flush();
        } else {
            $output->writeln('No workflow definitions found.');
        }
    }
}
