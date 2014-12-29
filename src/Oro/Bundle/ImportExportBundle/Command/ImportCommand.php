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
            ->setDescription('Import data from specified file for specified entity.')
            ->addArgument(
                self::ARGUMENT_FILE,
                InputArgument::REQUIRED,
                'File name, to import CSV data from'
            )
            ->addOption(
                self::OPTION_VALIDATION_PROCESSOR,
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the processor for the entity data validation contained in the CSV'
            )
            ->addOption(
                self::OPTION_PROCESSOR,
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the processor for the entity data contained in the CSV'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processorAlias = $this->handleProcessorName($input, $output);
        $sourceFile = $input->getArgument(self::ARGUMENT_FILE);
        $noInteraction = $input->getOption('no-interaction');

        $this->getImportHandler()->setImportingFileName($sourceFile);

        if (!$noInteraction) {
            $validationProcessorAlias = $this->handleProcessorName(
                $input,
                $output,
                self::OPTION_VALIDATION_PROCESSOR,
                ProcessorRegistry::TYPE_IMPORT_VALIDATION
            );

            $validationInfo = $this->getImportHandler()->handleImportValidation(
                JobExecutor::JOB_VALIDATE_IMPORT_FROM_CSV,
                $validationProcessorAlias
            );

            $this->renderResult($validationInfo, $output);

            $confirmation = $this->getHelper('dialog')->askConfirmation(
                $output,
                '<question>Do you want to proceed [yes]? </question>',
                true
            );

            if (!$confirmation) {
                return self::STATUS_SUCCESS;
            }
        }

        $importInfo = $this->getImportHandler()->handleImport(
            JobExecutor::JOB_IMPORT_FROM_CSV,
            $processorAlias
        );

        if ($noInteraction) {
            $this->renderResult($importInfo, $output);
        }

        $output->writeln('<info>' . $importInfo['message'] . '</info>');

        return self::STATUS_SUCCESS;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $option
     * @param string          $type
     * @return string
     * @throws \InvalidArgumentException
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

    /**
     * @param array $result
     * @param OutputInterface $output
     */
    protected function renderResult(array $result, OutputInterface $output)
    {
        $rows = [];
        if (!empty($result['counts'])) {
            foreach ($result['counts'] as $label => $count) {
                $rows[] = [$label, (int)$count];
            }
        }

        if ($rows) {
            $this
                ->getHelper('table')
                ->setHeaders(['Results', 'Count'])
                ->setRows($rows)
                ->render($output);
        }

        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $errorMessage) {
                $output->writeln('<error>' . $errorMessage . '</error>');
            }
        }
    }
}
