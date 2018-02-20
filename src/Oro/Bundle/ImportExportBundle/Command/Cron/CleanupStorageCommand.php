<?php

namespace Oro\Bundle\ImportExportBundle\Command\Cron;

use Gaufrette\File;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupStorageCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const DEFAULT_PERIOD = 14; // days

    /**
     * Set up Adapters that support old files removing. If empty then  all adapters files will be removed.
     *
     * @var array
     */
    private static $supportedAdapters = [];

    /**
     * @inheritdoc
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
        $classAdapter = get_class($this->getFileManager()->getAdapter());
        if (empty(self::$supportedAdapters)) {
            return true;
        }

        return in_array($classAdapter, self::$supportedAdapters);
    }

    /**
     * {@internaldoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:cron:import-clean-up-storage')
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

        list($from, $to) = $this->getDateRangeByPeriod($period);
        $fileManager = $this->getFileManager();
        $files = $fileManager->getFilesByPeriod($from, $to);

        /** @var File $file*/
        foreach ($files as $fileName => $file) {
            $fileManager->deleteFile($file);
            $output->writeln(
                sprintf('<info> File "%s" was removed.</info>', $fileName),
                OutputInterface::VERBOSITY_DEBUG
            );
        }

        $output->writeln(sprintf('<info>Were removed "%s" files.</info>', count($files)));

        return;
    }

    /**
     * @param integer $period
     * @return array
     */
    private function getDateRangeByPeriod($period)
    {
        $toDate = new \DateTime();
        $toDate->modify(sprintf('-%d days', $period));
        $fromDate = new \DateTime('@0');

        return [$fromDate, $toDate];
    }

    /**
     * @return FileManager
     */
    private function getFileManager()
    {
        return $this->getContainer()->get('oro_importexport.file.file_manager');
    }
}
