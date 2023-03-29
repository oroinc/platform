<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureSetup;
use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\OroMainContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Checks there are no active jobs before Behat step and runs consumer if it's not disabled
 */
class JobStatusSubscriber implements EventSubscriberInterface
{
    private const ACTIVE_JOB_STATUSES = [Job::STATUS_NEW, Job::STATUS_RUNNING, Job::STATUS_FAILED_REDELIVERED];
    private \DateTime $startDateTime;
    private ?Process  $process = null;
    private string $phpExecutablePath;
    private bool   $shouldNotRunConsumer = false;

    public function __construct(private KernelInterface $kernel)
    {
        $this->startDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->phpExecutablePath = (new PhpExecutableFinder())->find();
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeStepTested::BEFORE => ['beforeStep', -500],
            AfterFeatureTested::AFTER => ['afterFeature', 1000],
            AfterFeatureSetup::AFTER_SETUP => ['afterFeatureSetup', 1000],
        ];
    }

    public function afterFeatureSetup(AfterFeatureSetup $event)
    {
        $this->startConsumerIfNotRunning();
    }

    public function afterFeature(AfterFeatureTested $event)
    {
        $this->allJobsAreProcessed();
    }

    public function beforeStep(BeforeStepTested $event)
    {
        if (preg_match(OroMainContext::SKIP_WAIT_PATTERN, $event->getStep()->getText())) {
            // Don't wait when we need assert the flash message, because it can disappear until ajax in process
            return;
        }

        $this->allJobsAreProcessed();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function allJobsAreProcessed(): void
    {
        if (!$this->kernel->getContainer()->get(ApplicationState::class)->isInstalled()) {
            return;
        }
        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->kernel->getContainer()->get('doctrine');
        /** @var Connection $connection */
        $connection = $doctrine->getManagerForClass(Job::class)->getConnection();

        $endTime = new \DateTime('+900 seconds');
        while (true) {
            $this->startConsumerIfNotRunning();

            $activeJobs = $connection->fetchFirstColumn(
                'SELECT j.name FROM oro_message_queue_job j WHERE j.status IN (?) AND j.created_at > ?',
                [self::ACTIVE_JOB_STATUSES, $this->startDateTime],
                [Connection::PARAM_STR_ARRAY, Types::DATETIME_MUTABLE]
            );

            if (0 == count($activeJobs)) {
                return;
            }

            usleep(100000);

            $now = new \DateTime();
            if ($now >= $endTime) {
                break;
            }
        }

        throw new \RuntimeException(
            sprintf(
                'The application has not been able to finish processing jobs within the last 900 seconds. ' .
                'Unprocessed jobs: %s',
                implode(', ', $activeJobs)
            )
        );
    }

    private function startConsumerIfNotRunning(): void
    {
        if ($this->shouldNotRunConsumer) {
            return;
        }

        if ($this->process && $this->process->isRunning()) {
            return;
        }
        /** @var Filesystem $filesystem */
        $filesystem = $this->kernel->getContainer()->get('filesystem');
        $logDir = realpath($this->kernel->getLogDir());

        $command = [
            $this->phpExecutablePath,
            realpath($this->kernel->getProjectDir()) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'console',
            'oro:message-queue:consume',
            '-vv',
            sprintf('--env=%s', $this->kernel->getEnvironment()),
        ];

        $process = new Process($command);

        $process->start(function ($type, $buffer) use ($filesystem, $logDir) {
            if (Process::ERR === $type) {
                $this->getLogger()->error($buffer);
            }
            $filesystem->appendToFile(sprintf('%s/mq.log', $logDir), $buffer);
        });
        $this->process = $process;
    }

    private function getLogger(): LoggerInterface
    {
        return $this->kernel->getContainer()->get('monolog.logger.consumer');
    }

    public function doNotRunConsumer(): void
    {
        $this->shouldNotRunConsumer = true;
    }
}
