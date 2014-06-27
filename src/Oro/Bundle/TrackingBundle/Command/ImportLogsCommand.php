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
        return '*/5 * * * *';
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
        $fs        = new Filesystem();
        $finder    = new Finder();
        $className = $this->getContainer()->getParameter('oro_tracking.tracking_data.class');

        $directory = $this
                ->getContainer()
                ->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'tracking';

        if (!$fs->exists($directory)) {
            throw new \InvalidArgumentException(
                sprintf('Directory "%s" does not exists', $directory)
            );
        }

        $finder
            ->files()
            ->notName(
                sprintf('%s*.log', date('Ymd-H'))
            )
            ->in($directory);

        if (!$finder->count()) {
            throw new \InvalidArgumentException('All files were imported');
        }

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $pathName = $file->getPathname();
            $fileName = $file->getFilename();

            $options = [
                'entityName'     => $className,
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
}
