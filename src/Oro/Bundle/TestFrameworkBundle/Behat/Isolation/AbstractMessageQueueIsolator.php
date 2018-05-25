<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

abstract class AbstractMessageQueueIsolator extends AbstractOsRelatedIsolator implements
    IsolatorInterface,
    MessageQueueIsolatorInterface
{
    const CONSUMERS_AMOUNT = 1;

    /** @var KernelInterface */
    protected $kernel;

    /** @var Process[] */
    protected $processes = [];

    /** @var LoggerInterface */
    protected $logger;

    /** @var CacheState */
    protected $cacheState;

    /** @var \DateTime */
    protected $lastCacheStateChangeDate;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->logger = $this->kernel->getContainer()->get('logger');

        $this->cacheState = $this->kernel->getContainer()->get('oro_message_queue.consumption.cache_state');
        $this->lastCacheStateChangeDate = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
        $event->writeln('<info>Run message queue</info>');
        $this->startMessageQueue();
        $this->waitWhileProcessingMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function start(BeforeStartTestsEvent $event)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(AfterFinishTestsEvent $event)
    {
        try {
            $event->writeln('<info>Process message queue</info>');
            $this->startMessageQueue();
            $this->waitWhileProcessingMessages();
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $event->writeln('<info>Stop message queue</info>');
            $this->stopMessageQueue();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        try {
            $event->writeln('<info>Process message queue</info>');
            $this->startMessageQueue();
            $this->waitWhileProcessingMessages();
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $event->writeln('<info>Stop message queue</info>');
            $this->stopMessageQueue();
        }
    }

    /**
     * Checks state of MQ processors.
     * If no MQ processors is running, checks state of caches and if cache was changed - restarts MQ processors.
     *
     * @return bool
     */
    public function ensureMessageQueueIsRunning()
    {
        $isRunning = $this->isOutdatedState();
        if (!$isRunning) {
            $cacheChangeDate = $this->cacheState->getChangeDate();

            if (null === $cacheChangeDate) {
                $this->startMessageQueue();
                $isRunning = true;
            }

            if ($cacheChangeDate > $this->lastCacheStateChangeDate) {
                $this->lastCacheStateChangeDate = $cacheChangeDate;

                $this->startMessageQueue();
                $isRunning = true;
            }
        }

        return $isRunning;
    }

    /**
     * {@inheritdoc]
     */
    public function startMessageQueue()
    {
        // start processes if they are not initialized
        // lazy loading, allows to start MQ without status control
        if (!$this->processes) {
            $command = sprintf(
                'php %s/console oro:message-queue:consume -vv --env=%s %s >> %s/mq.log',
                realpath($this->kernel->getProjectDir()) . '/bin/',
                $this->kernel->getEnvironment(),
                $this->kernel->isDebug() ? '' : '--no-debug',
                realpath($this->kernel->getLogDir())
            );

            for ($i = 1; $i <= self::CONSUMERS_AMOUNT; $i++) {
                $process = new Process($command);
                $this->startProcess($process);

                $this->processes[$i] = $process;
            }
        }

        // make sure processes are running
        // update status (case when process terminated by pkill but status is still running)
        // ignore already running exception, it's our goal to ensure MQ is running
        foreach ($this->processes as $process) {
            if ($process->getStatus() && $process->isRunning()) {
                continue;
            }

            $this->startProcess($process);
        }
    }

    private function startProcess(Process $process)
    {
        $process
            ->setTimeout(null)
            ->start(
                function ($type, $buffer) {
                    if (Process::ERR === $type) {
                        $this->logger->error($buffer);
                    }
                }
            );
    }

    /**
     * {@inheritdoc}
     */
    public function stopMessageQueue()
    {
        if (self::WINDOWS_OS === $this->getOs()) {
            $killCommand = sprintf('TASKKILL /IM %s/console /T /F', realpath($this->kernel->getProjectDir()) . '/bin/');
        } else {
            $killCommand = sprintf(
                "pkill -15 -f '%s/[c]onsole oro:message-queue:consume'",
                realpath($this->kernel->getProjectDir()) . '/bin/'
            );
        }

        try {
            // Process::stop() might not work as expected
            // See https://github.com/symfony/symfony/issues/5030
            $process = new Process($killCommand);
            $process
                ->run(
                    function ($type, $buffer) {
                        if (Process::ERR === $type) {
                            $this->logger->error($buffer);
                        }
                    }
                );

            // wait for kill process, database isolator running to fast and stopped my MQ ping query
            sleep(1);
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $this->cleanUp();

            /**
             * Update origin process status, mark it terminated to disable
             */
            foreach ($this->processes as $process) {
                $process->stop();
            }
        }
    }

    abstract protected function cleanUp();

    /**
     * {@inheritdoc}
     */
    public function isOutdatedState()
    {
        foreach ($this->processes as $process) {
            if ($process->getStatus() && $process->isRunning()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function restoreState(RestoreStateEvent $event)
    {
        try {
            $event->writeln('<info>Process message queue</info>');
            $this->waitWhileProcessingMessages();
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $event->writeln('<info>Stop message queue</info>');
            $this->stopMessageQueue();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'message-queue';
    }

    /**
     * {@inheritdoc}
     */
    protected function getApplicableOs()
    {
        return [
            self::WINDOWS_OS,
            self::LINUX_OS,
            self::MAC_OS,
        ];
    }
}
