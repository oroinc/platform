<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class LoadWorkflowDefinitionsCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:workflow:definitions:load')
            ->setDescription('Load workflow definitions from specified directories to your database.')
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

        /** @var WorkflowConfigurationProvider $configurationProvider */
        $configurationProvider = $this->getContainer()->get('oro_workflow.configuration.provider.workflow_config');
        /** @var WorkflowDefinitionConfigurationBuilder $configurationBuilder */
        $configurationBuilder = $this->getContainer()->get('oro_workflow.configuration.builder.workflow_definition');
        /** @var EntityManager $manager */
        $manager = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        $workflowConfiguration = $configurationProvider->getWorkflowDefinitionConfiguration(
            $usedDirectories,
            $usedWorkflows
        );

        if ($workflowConfiguration) {
            $output->writeln('Loading workflow definitions...');

            $workflowDefinitions = $configurationBuilder->buildFromConfiguration($workflowConfiguration);

            /** @var WorkflowDefinitionRepository $workflowDefinitionRepository */
            $workflowDefinitionRepository = $manager->getRepository('OroWorkflowBundle:WorkflowDefinition');
            foreach ($workflowDefinitions as $workflowDefinition) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $workflowDefinition->getName()));

                /** @var WorkflowDefinition $existingWorkflowDefinition */
                $existingWorkflowDefinition = $workflowDefinitionRepository->find($workflowDefinition->getName());

                // workflow definition should be overridden if workflow definition with such name already exists
                if ($existingWorkflowDefinition) {
                    $existingWorkflowDefinition->import($workflowDefinition);
                } else {
                    $manager->persist($workflowDefinition);
                }
            }
        } else {
            $output->writeln('No workflow definitions found.');
        }

        $manager->flush();
    }
}
