<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationBuilder;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;

class LoadProcessConfigurationCommand extends ContainerAwareCommand
{
    const NAME = 'oro:process:configuration:load';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
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
     * {@inheritdoc}
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

        // create cron schedules for process triggers
        $this->processCronSchedules($output);

        // update triggers cache
        $this->getContainer()->get('oro_workflow.cache.process_trigger')->build();
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

            $entityManager = $this->getEntityManager('OroWorkflowBundle:ProcessDefinition');
            $definitionRepository = $this->getRepository('OroWorkflowBundle:ProcessDefinition');

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

    /**
     * @param OutputInterface $output
     * @param array $configuration
     */
    protected function loadTriggers(OutputInterface $output, array $configuration)
    {
        /** @var ProcessDefinition[] $allDefinitions */
        $allDefinitions = $this->getRepository('OroWorkflowBundle:ProcessDefinition')->findAll();
        $definitionsByName = [];
        foreach ($allDefinitions as $definition) {
            $definitionsByName[$definition->getName()] = $definition;
        }

        $triggers = $this->getConfigurationBuilder()->buildProcessTriggers($configuration, $definitionsByName);

        if ($triggers) {
            $output->writeln('Loading process triggers...');

            $entityManager = $this->getEntityManager('OroWorkflowBundle:ProcessTrigger');
            $triggerRepository = $entityManager->getRepository('OroWorkflowBundle:ProcessTrigger');

            foreach ($triggers as $trigger) {
                $output->writeln(
                    sprintf(
                        '  <comment>></comment> <info>%s:%s</info>',
                        $trigger->getDefinition()->getName(),
                        $trigger->getEvent() ?: 'cron:' . $trigger->getCron()
                    )
                );

                $existingTrigger = $triggerRepository->findEqualTrigger($trigger);
                if ($existingTrigger) {
                    $existingTrigger->import($trigger);
                } else {
                    $entityManager->persist($trigger);
                }
            }

            $entityManager->flush();
        } else {
            $output->writeln('No process triggers found.');
        }
    }

    /**
     * @param OutputInterface $output
     */
    protected function processCronSchedules(OutputInterface $output)
    {
        $triggers = $this->getCronTriggers();

        if ($triggers) {
            $output->writeln('Loading cron schedules for process triggers...');

            $command = HandleProcessTriggerCommand::NAME;
            $entityManager = $this->getEntityManager('OroCronBundle:Schedule');
            $scheduleManager = $this->getContainer()->get('oro_cron.schedule_manager');

            foreach ($triggers as $trigger) {
                $arguments = [
                    sprintf('--name=%s', $trigger->getDefinition()->getName()),
                    sprintf('--id=%d', $trigger->getId())
                ];

                $output->writeln(
                    sprintf(
                        '  <comment>></comment> <info>[%s] %s %s</info>',
                        $trigger->getCron(),
                        $command,
                        implode(' ', $arguments)
                    )
                );

                if (!$scheduleManager->hasSchedule($command, $arguments, $trigger->getCron())) {
                    $schedule = $scheduleManager->createSchedule($command, $arguments, $trigger->getCron());

                    $entityManager->persist($schedule);
                }
            }

            $entityManager->flush();
        } else {
            $output->writeln('No enabled process triggers with cron expression found.');
        }
    }

    /**
     * @param string $className
     * @return ObjectManager
     */
    protected function getEntityManager($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className);
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getEntityManager($className)->getRepository($className);
    }

    /**
     * @return ProcessConfigurationBuilder
     */
    protected function getConfigurationBuilder()
    {
        return $this->getContainer()->get('oro_workflow.configuration.builder.process_configuration');
    }

    /**
     * @return array|ProcessTrigger[]
     */
    protected function getCronTriggers()
    {
        /** @var ProcessTriggerRepository $repository */
        $repository = $this->getRepository('OroWorkflowBundle:ProcessTrigger');

        return $repository->findAllCronTriggers();
    }
}
