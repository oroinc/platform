<?php

namespace Oro\Bundle\ImportExportBundle\Command;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\CliImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:import:csv')
            ->setDescription(
                'Import data from specified file for specified entity. The import log is sent to the provided email.'
            )
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'File name, to import CSV data from'
            )
            ->addOption(
                'validation',
                null,
                InputOption::VALUE_NONE,
                'If adding this option then validation will be performed instead of import'
            )
            ->addOption(
                'processor',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the processor for the entity data contained in the CSV (validation or import)'
            )
            ->addOption(
                'email',
                null,
                InputOption::VALUE_REQUIRED,
                'Email to send the log after the import is completed'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sourceFile = $input->getArgument('file');

        if (! is_file($sourceFile)) {
            throw new \InvalidArgumentException(sprintf('File not found: %s', $sourceFile));
        }

        $validation = $input->hasOption('validation');

        $this->getImportHandler()->setImportingFileName($sourceFile);

        $processorAlias = $this->handleProcessorName(
            $input,
            $output,
            $validation ? ProcessorRegistry::TYPE_IMPORT_VALIDATION : ProcessorRegistry::TYPE_IMPORT
        );

        $topic = $validation ? Topics::IMPORT_CLI_VALIDATION : Topics::IMPORT_CLI;
        $jobName = $validation ? JobExecutor::JOB_VALIDATE_IMPORT_FROM_CSV : JobExecutor::JOB_IMPORT_FROM_CSV;
        $email = $input->getOption('email');

        if ($validation && ! $email) {
            throw new \InvalidArgumentException('Email is required for the validation!');
        }

        $this->getMessageProducer()->send(
            $topic,
            [
                'fileName' => $sourceFile,
                'notifyEmail' => $email,
                'jobName' =>  $jobName,
                'processorAlias' => $processorAlias
            ]
        );

        $output->writeln(
            sprintf('%s sheduled successfully. The result will be sent to the email ')
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $type
     * @return string
     * @throws \InvalidArgumentException
     */
    private function handleProcessorName(
        InputInterface $input,
        OutputInterface $output,
        $type = ProcessorRegistry::TYPE_IMPORT
    ) {
        $option = 'processor';

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
    private function getImportHandler()
    {
        return $this->getContainer()->get('oro_importexport.handler.import.cli');
    }

    /**
     * @return ProcessorRegistry
     */
    private function getProcessorRegistry()
    {
        return $this->getContainer()->get('oro_importexport.processor.registry');
    }

    /**
     * @return MessageProducerInterface
     */
    private function getMessageProducer()
    {
        return $this->getContainer()->get('oro_message_queue.client.message_producer');
    }
}
