<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
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
            ->setDescription('Load workflow definitions from specified package(s) to your database.')
            ->addArgument(
                'package',
                InputArgument::IS_ARRAY,
                'Package directories'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // prepare directories of specified packages
        $packageDirectories = $input->getArgument('package');
        foreach ($packageDirectories as $key => $packageDir) {
            $packageDirectories[$key] = realpath($packageDir) . DIRECTORY_SEPARATOR;
        }

        // a function which allows filter workflow definitions by the given packages
        $filterByPackage = function ($path) use (&$packageDirectories) {
            if (empty($packageDirectories)) {
                return true;
            }

            foreach ($packageDirectories as $packageDir) {
                if (stripos($path, $packageDir) === 0) {
                    return true;
                }
            }

            return false;
        };

        $output->writeln('Loading workflow definitions ...');

        /** @var WorkflowConfigurationProvider $configurationProvider */
        $configurationProvider = $this->getContainer()->get('oro_workflow.configuration.provider.workflow_config');
        /** @var WorkflowDefinitionConfigurationBuilder $configurationBuilder */
        $configurationBuilder = $this->getContainer()->get('oro_workflow.configuration.builder.workflow_definition');
        /** @var EntityManager $manager */
        $manager = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        $workflowConfiguration = $configurationProvider->getWorkflowDefinitionConfiguration($filterByPackage);
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

        $manager->flush();
    }
}
