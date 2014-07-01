<?php

namespace Oro\Bundle\TrackingBundle\Command;

use Akeneo\Bundle\BatchBundle\Job\BatchStatus;

use Doctrine\ORM\QueryBuilder;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

class ImportLogsCommand extends ContainerAwareCommand implements CronCommandInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '1 * * * *';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:cron:import-tracking')
            ->setDescription('Import tracking logs');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $fs     = new Filesystem();
        $finder = new Finder();

        $directory = $this
                ->getContainer()
                ->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'tracking';

        $ignoredFilename = $this->getIgnoredFilename();
        $finder
            ->files()
            ->filter(
                function (\SplFileInfo $file) use ($ignoredFilename) {
                    return $ignoredFilename != $file->getFilename();
                }
            )
            ->in($directory);

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $pathName = $file->getPathname();
            $fileName = $file->getFilename();

            $options = [
                'entityName'     => $this->getContainer()->getParameter('oro_tracking.tracking_data.class'),
                'processorAlias' => 'oro_tracking.processor.data',
                'file'           => $pathName
            ];

            if ($this->isFileProcessed($options)) {
                continue;
            }

            $jobResult = $this->getJobExecutor()->executeJob(
                ProcessorRegistry::TYPE_IMPORT,
                'import_log_to_database',
                ['import' => $options]
            );

            if ($jobResult->isSuccessful()) {
                $output->writeln(
                    sprintf('<info>Successful</info>: "%s"', $fileName)
                );
                $fs->remove($pathName);
            } else {
                foreach ($jobResult->getFailureExceptions() as $exception) {
                    $output->writeln(
                        sprintf(
                            '<error>Error</error>: "%s".',
                            $exception
                        )
                    );
                }
            }
        }
    }

    /**
     * @return JobExecutor
     */
    protected function getJobExecutor()
    {
        return $this->getContainer()->get('oro_importexport.job_executor');
    }

    /**
     * @param array $options
     * @return bool
     */
    protected function isFileProcessed(array $options)
    {
        $className = 'Akeneo\Bundle\BatchBundle\Entity\JobExecution';

        $qb = $this
            ->getContainer()
            ->get('doctrine')
            ->getManagerForClass($className)
            ->getRepository($className)
            ->createQueryBuilder('je');

        /** @var QueryBuilder $qb */
        $result = $qb
            ->select('COUNT(je) as jobs')
            ->leftJoin('je.jobInstance', 'ji')
            ->where('je.status NOT IN (:statuses)')
            ->setParameter(
                'statuses',
                [BatchStatus::STARTING, BatchStatus::STARTED]
            )
            ->andWhere('ji.rawConfiguration = :rawConfiguration')
            ->setParameter('rawConfiguration', serialize($options))
            ->getQuery()
            ->getOneOrNullResult();

        return $result['jobs'];
    }

    /**
     * @return string
     */
    protected function getIgnoredFilename()
    {
        $config            = $this->getContainer()->get('oro_config.user');
        $logRotateInterval = $config->get('oro_tracking.log_rotate_interval');

        $rotateInterval = 60;
        $currentPart    = 1;
        if ($logRotateInterval > 0 && $logRotateInterval < 60) {
            $rotateInterval = (int)$logRotateInterval;
            $passingMinute  = intval(date('i')) + 1;
            $currentPart    = ceil($passingMinute / $rotateInterval);
        }

        return date('Ymd-H') . '-' . $rotateInterval . '-' . $currentPart . '.log';
    }
}
