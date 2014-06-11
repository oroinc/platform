<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;

class LoadProcessConfigurationCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EntityRepository
     */
    protected $definitionRepository;

    /**
     * @var ProcessTriggerRepository
     */
    protected $triggerRepository;

    /**
     * @var ProcessConfigurationBuilder
     */
    protected $configurationBuilder;

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        }

        return $this->entityManager;
    }

    /**
     * @return EntityRepository
     */
    protected function getDefinitionRepository()
    {
        if (!$this->definitionRepository) {
            $this->definitionRepository
                = $this->getEntityManager()->getRepository('OroWorkflowBundle:ProcessDefinition');
        }

        return $this->definitionRepository;
    }

    /**
     * @return ProcessTriggerRepository
     */
    protected function getTriggerRepository()
    {
        if (!$this->triggerRepository) {
            $this->triggerRepository
                = $this->getEntityManager()->getRepository('OroWorkflowBundle:ProcessTrigger');
        }

        return $this->triggerRepository;
    }

    /**
     * @return ProcessConfigurationBuilder
     */
    protected function getConfigurationBuilder()
    {
        if (!$this->configurationBuilder) {
            $this->configurationBuilder
                = $this->getContainer()->get('oro_workflow.configuration.builder.process_configuration');
        }

        return $this->configurationBuilder;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:process:configuration:load')
            ->setDescription('Load process configuration from configuration files to the database')
            ->addOption(
                'directories',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Directories used to find configuration files'
            )
            ->addOption(
                'definitions',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Names of the process definitions that should be loaded'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $usedDirectories = $input->getOption('directories');
        $usedDirectories = $usedDirectories ?: null;

        $usedDefinitions = $input->getOption('definitions');
        $usedDefinitions = $usedDefinitions ?: null;

        /** @var ProcessConfigurationProvider $configurationProvider */
        $configurationProvider = $this->getContainer()->get('oro_workflow.configuration.provider.process_config');
        $processConfiguration = $configurationProvider->getProcessConfiguration(
            $usedDirectories,
            $usedDefinitions
        );

        // process definitions
        $definitionsConfiguration = $processConfiguration[ProcessConfigurationProvider::NODE_DEFINITIONS];
        $this->loadDefinitions($output, $definitionsConfiguration);

        // process triggers
        $triggersConfiguration = $processConfiguration[ProcessConfigurationProvider::NODE_TRIGGERS];
        $this->loadTriggers($output, $triggersConfiguration);
    }

    /**
     * @param OutputInterface $output
     * @param array $configuration
     */
    protected function loadDefinitions(OutputInterface $output, array $configuration)
    {
        $definitions = $this->getConfigurationBuilder()->buildProcessDefinitions($configuration);

        if ($definitions) {
            $output->writeln('Loading process definitions...');

            $entityManager = $this->getEntityManager();
            $definitionRepository = $this->getDefinitionRepository();

            foreach ($definitions as $definition) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $definition->getName()));

                /** @var ProcessDefinition $existingDefinition */
                $existingDefinition = $definitionRepository->find($definition->getName());

                // definition should be overridden if definition with such name already exists
                if ($existingDefinition) {
                    $existingDefinition->import($definition);
                } else {
                    $entityManager->persist($definition);
                }
            }

            $entityManager->flush();
        } else {
            $output->writeln('No process definitions found.');
        }
    }

    protected function loadTriggers(OutputInterface $output, array $configuration)
    {
        /** @var ProcessDefinition[] $allDefinitions */
        $allDefinitions = $this->getDefinitionRepository()->findAll();
        $definitionsByName = array();
        foreach ($allDefinitions as $definition) {
            $definitionsByName[$definition->getName()] = $definition;
        }

        $triggers = $this->configurationBuilder->buildProcessTriggers($configuration, $definitionsByName);

        if ($triggers) {
            $output->writeln('Loading process triggers...');

            $entityManager = $this->getEntityManager();
            $triggerRepository = $this->getTriggerRepository();

            foreach ($triggers as $trigger) {
                $output->writeln(
                    sprintf(
                        '  <comment>></comment> <info>%s:%s</info>',
                        $trigger->getDefinition()->getName(),
                        $trigger->getEvent()
                    )
                );

                /** @var ProcessDefinition $existingDefinition */
                if (!$triggerRepository->isEqualTriggerExists($trigger)) {
                    $entityManager->persist($trigger);
                }
            }

            $entityManager->flush();
        } else {
            $output->writeln('No process triggers found.');
        }
    }
}
