<?php

namespace Oro\Bundle\ImportExportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\ImportExportBundle\Handler\CliImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

class ImportCommand extends ContainerAwareCommand
{
    const STATUS_SUCCESS = 0;

    const COMMAND_NAME = 'oro:import:csv';

    const ARGUMENT_FILE = 'file';
    const OPTION_VALIDATION_PROCESSOR = 'validation-processor';
    const OPTION_PROCESSOR = 'processor';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->addOption(
                self::OPTION_PROCESSOR,
                null,
                InputOption::VALUE_OPTIONAL,
                'Name of the processor for the entity data contained in the CSV'
            )
            ->addOption(
                self::OPTION_VALIDATION_PROCESSOR,
                null,
                InputOption::VALUE_OPTIONAL,
                'Name of the processor for the entity data validation contained in the CSV'
            )
            ->addArgument(
                self::ARGUMENT_FILE,
                InputArgument::REQUIRED,
                'File name from which to import the CSV data'
            )
            ->setDescription('Import data from specified file for specified entity.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processorAlias = $this->handleProcessorName($input, $output);
        $validationProcessorAlias = $this->handleProcessorName(
            $input,
            $output,
            self::OPTION_VALIDATION_PROCESSOR,
            ProcessorRegistry::TYPE_IMPORT_VALIDATION
        );

        $srcFile = $input->getArgument(self::ARGUMENT_FILE);

        $this->getImportHandler()->setImportingFileName($srcFile);

        $validationInfo = $this->getImportHandler()->handleImportValidation(
            JobExecutor::JOB_VALIDATE_IMPORT_FROM_CSV,
            $validationProcessorAlias
        );

        $rows = [];
        foreach ($validationInfo['counts'] as $label => $count) {
            $rows[] = [$label, (int)$count];
        }

        if ($rows) {
            $this
                ->getHelper('table')
                ->setHeaders(['Results', 'Count'])
                ->setRows($rows)
                ->render($output);
        }

        if ($validationInfo['success']) {
            if ($input->getOption('no-interaction')) {
                return self::STATUS_SUCCESS;
            }

            $confirmation = $this->getHelper('dialog')->askConfirmation(
                $output,
                '<question>Do you want to proceed [yes]? </question>',
                true
            );

            if (!$confirmation) {
                return self::STATUS_SUCCESS;
            }

            $importInfo = $this->getImportHandler()->handleImport(
                JobExecutor::JOB_IMPORT_FROM_CSV,
                $processorAlias
            );

            $output->writeln('<info>' . $importInfo['message'] . '</info>');
        } else {
            foreach ($validationInfo['errors'] as $errorMessage) {
                $output->writeln('<error>' . $errorMessage . '</error>');
            }
        }

        return self::STATUS_SUCCESS;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $option
     * @param string          $type
     *
     * @return string
     */
    protected function handleProcessorName(
        InputInterface $input,
        OutputInterface $output,
        $option = self::OPTION_PROCESSOR,
        $type = ProcessorRegistry::TYPE_IMPORT
    ) {
        $label = ucwords(str_replace('-', ' ', $option));

        if ($processor = $input->getOption($option)) {
            return $processor;
        }

        $processors = $this->getProcessorRegistry()->getProcessorsByType($type);

        if (!$processors) {
            throw new \InvalidArgumentException('No configured processors');
        }

        $processorNames = array_keys($processors);
        if (!$input->getOption('no-interaction')) {
            $selected = $this->getHelper('dialog')->select(
                $output,
                sprintf('<question>Choose %s: </question>', $label),
                $processorNames
            );

            return $processorNames[$selected];
        }

        throw new \InvalidArgumentException(sprintf('Missing %s', $label));
    }

    /**
     * @return CliImportHandler
     */
    protected function getImportHandler()
    {
        return $this->getContainer()->get('oro_importexport.handler.import.cli');
    }

    /**
     * @return ProcessorRegistry
     */
    protected function getProcessorRegistry()
    {
        return $this->getContainer()->get('oro_importexport.processor.registry');
    }
}
