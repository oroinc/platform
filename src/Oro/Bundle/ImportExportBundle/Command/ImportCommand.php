<?php

namespace Oro\Bundle\ImportExportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\ImportExportBundle\Handler\CliImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;

class ImportCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:import:csv';

    const PROCESSOR_ALIAS_ARGUMENT_NAME = 'processor';
    const SRC_ARGUMENT_NAME = 'file';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::COMMAND_NAME)
            ->addArgument(
                static::PROCESSOR_ALIAS_ARGUMENT_NAME,
                InputArgument::REQUIRED,
                'Name of the processor for the entity data contained in the CSV'
            )
            ->addArgument(
                static::SRC_ARGUMENT_NAME,
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
        $processorAlias = $input->getArgument(static::PROCESSOR_ALIAS_ARGUMENT_NAME);
        $srcFile = $input->getArgument(static::SRC_ARGUMENT_NAME);

        $this->getImportHandler()->setImportingFileName($srcFile);

        $validationInfo = $this->getImportHandler()->handleImportValidation(
            JobExecutor::JOB_VALIDATE_IMPORT_FROM_CSV,
            $processorAlias
        );

        foreach ($validationInfo['counts'] as $label => $count) {
            $output->writeln('<comment>' . $label . '...' . $count . '</comment>');
        }

        if ($validationInfo['isSuccessful']) {
            if (!$input->getOption('no-interaction') && !$this->getHelper('dialog')->askConfirmation(
                $output,
                '<question>Do you want to proceed [yes]?</question>',
                true
            )) {
                return 0;
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

        return 0;
    }

    /**
     * @return CliImportHandler
     */
    protected function getImportHandler()
    {
        return $this->getContainer()->get('oro_importexport.handler.import.cli');
    }
}
