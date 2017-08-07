<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

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

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->logger = $this->kernel->getContainer()->get('logger');
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
        $this->startMessageQueue();
        $this->waitWhileProcessingMessages();
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
        $this->startMessageQueue();
        $event->writeln('<info>Run message queue</info>');
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
        try {
            $this->startMessageQueue();
            $this->waitWhileProcessingMessages();
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $event->writeln('<info>Stop message queue</info>');
            $this->stopMessageQueue();
        }
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        try {
            $this->startMessageQueue();
            $this->waitWhileProcessingMessages();
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $event->writeln('<info>Process message queue</info>');
            $this->stopMessageQueue();
        }
    }

    public function startMessageQueue()
    {
        // start processes if they are not initialized
        // lazy loading, allows to start MQ without status control
        if (!$this->processes) {
            $command = sprintf(
                'php %s/console oro:message-queue:consume -vvv --env=%s %s >> %s/mq.log',
                realpath($this->kernel->getRootDir()),
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

    public function stopMessageQueue()
    {
        if (self::WINDOWS_OS === $this->getOs()) {
            $killCommand = sprintf('TASKKILL /IM %s/console /T /F', realpath($this->kernel->getRootDir()));
        } else {
            $killCommand = sprintf(
                "pkill -9 -f '%s/[c]onsole oro:message-queue:consume'",
                realpath($this->kernel->getRootDir())
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
        $isRunning = false;
        foreach ($this->processes as $process) {
            $isRunning = $isRunning || ($process->getStatus() && $process->isRunning());
        }

        return $isRunning;
    }

    /**
     * {@inheritdoc}
     */
    public function restoreState(RestoreStateEvent $event)
    {
        try {
            $this->waitWhileProcessingMessages();
        } catch (\Exception $e) {
            throw $e;
        } finally {
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
