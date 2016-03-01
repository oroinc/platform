<?php

namespace Oro\Bundle\TrackingBundle\Command;

use Akeneo\Bundle\BatchBundle\Job\BatchStatus;
use Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->setDescription('Import tracking logs')
            ->addOption(
                'directory',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Logs directory'
            );

    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $fs     = new Filesystem();
        $finder = new Finder();

        if (!$directory = $input->getOption('directory')) {
            $directory = $this
                    ->getContainer()
                    ->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'tracking';
        }

        if (!$fs->exists($directory)) {
            $fs->mkdir($directory);

            $output->writeln('<info>Logs not found</info>');

            return;
        }

        $finder
            ->files()
            ->notName($this->getIgnoredFilename())
            ->notName('settings.ser')
            ->in($directory);

        if (!$finder->count()) {
            $output->writeln('<info>Logs not found</info>');

            return;
        }

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $pathName = $file->getPathname();
            $fileName = $file->getFilename();

            $options = [
                ProcessorRegistry::TYPE_IMPORT => [
                    'entityName' => $this->getContainer()->getParameter('oro_tracking.tracking_data.class'),
                    'processorAlias' => 'oro_tracking.processor.data',
                    'file' => $pathName,
                ],
            ];

            if ($this->isFileProcessed($options)) {
                $output->writeln(sprintf('<info>"%s" already processed</info>', $fileName));

                continue;
            }

            $jobResult = $this->getJobExecutor()->executeJob(
                ProcessorRegistry::TYPE_IMPORT,
                'import_log_to_database',
                $options
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
        /** @var DoctrineJobRepository $repo */
        $repo = $this->getContainer()->get('akeneo_batch.job_repository');

        $manager = $repo->getJobManager();

        $qb = $manager
            ->getRepository('Akeneo\Bundle\BatchBundle\Entity\JobExecution')
            ->createQueryBuilder('je');

        /** @var QueryBuilder $qb */
        $result = $qb
            ->select('COUNT(je) as jobs')
            ->leftJoin('je.jobInstance', 'ji')
            ->where($qb->expr()->lt('je.status', ':status'))
            ->setParameter('status', BatchStatus::FAILED)
            ->andWhere('ji.rawConfiguration = :rawConfiguration')
            ->setParameter(
                'rawConfiguration',
                $manager->getConnection()->convertToDatabaseValue($options, Type::TARRAY)
            )
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
            $passingMinute  = (int)(date('i')) + 1;
            $currentPart    = ceil($passingMinute / $rotateInterval);
        }

        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        return $date->format('Ymd-H') . '-' . $rotateInterval . '-' . $currentPart . '.log';
    }
}
