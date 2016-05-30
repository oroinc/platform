<?php

namespace Oro\Bundle\WorkflowBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

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

        $processImport = $this->getContainer()->get('oro_workflow.process.storage');

        $result = $processImport->import($processConfiguration);

        $this->printDefinitions($output, $result->getDefinitions());
        $this->printTriggers($output, $result->getTriggers());
        $this->printSchedules($output, $result->getSchedules());

        // update triggers cache
        $this->getContainer()->get('oro_workflow.cache.process_trigger')->build();
    }

    /**
     * @param OutputInterface $output
     * @param ProcessDefinition[] $definitions
     */
    protected function printDefinitions(OutputInterface $output, array $definitions)
    {
        if (count($definitions) !== 0) {
            $output->writeln('Loaded process definitions:');
            foreach ($definitions as $definition) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $definition->getName()));
            }
        } else {
            $output->writeln('No process definitions found.');
        }
    }

    /**
     * @param OutputInterface $output
     * @param array|ProcessTrigger[] $triggers
     */
    protected function printTriggers(OutputInterface $output, array $triggers)
    {
        if (count($triggers) !== 0) {
            $output->writeln('Loaded process triggers:');
            foreach ($triggers as $trigger) {
                $output->writeln(
                    sprintf(
                        '  <comment>></comment> <info>%s:%s</info>',
                        $trigger->getDefinition()->getName(),
                        $trigger->getEvent() ?: 'cron:' . $trigger->getCron()
                    )
                );
            }
        } else {
            $output->writeln('No process triggers found.');
        }
    }

    /**
     * @param OutputInterface $output
     * @param array|Schedule[] $schedules
     */
    protected function printSchedules(OutputInterface $output, array $schedules)
    {
        if (count($schedules) !== 0) {
            $output->writeln('Loaded process schedules:');
            foreach ($schedules as $schedule) {
                $output->writeln(
                    sprintf(
                        '  <comment>></comment> <info>[%s] %s %s</info>',
                        $schedule->getDefinition(),
                        $schedule->getCommand(),
                        implode(' ', $schedule->getArguments())
                    )
                );
            }
        } else {
            $output->writeln('No enabled process triggers with cron expression found.');
        }
    }
}
