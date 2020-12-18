<?php

namespace Oro\Bundle\ImportExportBundle\Command\Cron;

use Gaufrette\File;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Manager\ImportExportResultManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Responsible for deleting temporary files with a given periodicity
 */
class CleanupStorageCommand extends Command implements CronCommandInterface
{
    private const DEFAULT_PERIOD = 14; // days

    /** @var string */
    protected static $defaultName = 'oro:cron:import-clean-up-storage';

    /** @var FileManager */
    private $fileManager;

    /** @var ImportExportResultManager */
    private $importExportResultManager;

    /**
     * @param FileManager $fileManager
     * @param ImportExportResultManager $importExportResultManager
     */
    public function __construct(FileManager $fileManager, ImportExportResultManager $importExportResultManager)
    {
        $this->fileManager = $fileManager;
        $this->importExportResultManager = $importExportResultManager;

        parent::__construct();
    }

    /**
     * {@internaldoc}
     */
    public function getDefaultDefinition()
    {
        return '0 0 */1 * *';
    }

    /**
     * {@internaldoc}
     */
    public function isActive()
    {
        return true;
    }

    /**
     * {@internaldoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Clear old files from import storage.')
            ->addOption(
                'interval',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Time interval (days) to keep the import and export files.'.
                ' Will be removed files older than today-interval.',
                self::DEFAULT_PERIOD
            );
    }

    /**
     * {@internaldoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $period = (int)$input->getOption('interval');

        $from = new \DateTime('@0');
        $to = new \DateTime();
        $to->modify(sprintf('-%d days', $period));

        $this->importExportResultManager->markResultsAsExpired($from, $to);

        $files = $this->fileManager->getFilesByPeriod($from, $to);
        /** @var File $file*/
        foreach ($files as $fileName => $file) {
            $this->fileManager->deleteFile($file);
            $output->writeln(
                sprintf('<info> File "%s" was removed.</info>', $fileName),
                OutputInterface::VERBOSITY_DEBUG
            );
        }

        $output->writeln(sprintf('<info>Were removed "%s" files.</info>', count($files)));
    }
}
