<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

abstract class AbstractMessageQueueIsolator extends AbstractOsRelatedIsolator implements
    IsolatorInterface,
    MessageQueueIsolatorInterface
{
    /** @var KernelInterface */
    protected $kernel;

    /** @var Process */
    protected $process;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->logger = $this->kernel->getContainer()->get('logger');

        $command = sprintf(
            'php %s/console oro:message-queue:consume -vvv --env=%s %s >> %s/mq.log',
            realpath($this->kernel->getRootDir()),
            $this->kernel->getEnvironment(),
            $this->kernel->isDebug() ? '' : '--no-debug',
            realpath($this->kernel->getLogDir())
        );

        $this->process = new Process($command);
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
        $this->startProcess();
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        try {
            $this->waitWhileProcessingMessages();
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $this->killProcess();
        }
    }

    protected function startProcess()
    {
        $this->process
            ->setTimeout(null)
            ->start(
                function ($type, $buffer) {
                    if (Process::ERR === $type) {
                        $this->logger->error($buffer);
                    }
                }
            );
    }

    protected function killProcess()
    {
        if (self::WINDOWS_OS === $this->getOs()) {
            $killCommand = sprintf('TASKKILL /PID %d /T /F', $this->process->getPid());
        } else {
            $killCommand = sprintf('pkill -9 -f %s/[c]onsole', realpath($this->kernel->getRootDir()));
        }

        try {
            // Process::stop() might not work as expected
            // See https://github.com/symfony/symfony/issues/5030
            $process = new Process($killCommand);
            $process->run();
        } catch (\Exception $e) {
            throw $e;
        } finally {
            /**
             * Update origin process status, mark it terminated to disable
             * \Oro\Bundle\TestFrameworkBundle\Behat\Listener\MessageQueueRunCheckSubscriber
             */
            $this->process->stop();

            $this->cleanUp();
        }
    }

    abstract protected function cleanUp();

    /**
     * {@inheritdoc}
     */
    public function waitWhileProcessingMessages($timeLimit = 600)
    {
        $isIdleOutput = 0;

        while ($timeLimit >= 0) {
            if ($this->isIdleOutput()) {
                $isIdleOutput++;
            } else {
                $isIdleOutput = 0;
            }

            if ($isIdleOutput >= 5) {
                return;
            }

            if ($timeLimit <= 0) {
                throw new RuntimeException('Message Queue was not process messages during time limit');
            }

            sleep(1);
            $timeLimit -= 1;
        }
    }

    private function isIdleOutput()
    {
        $output = trim($this->process->getIncrementalOutput());

        // skip debug messages
        $buffers = explode(PHP_EOL, $output);
        foreach ($buffers as $buffer) {
            if (!$buffer) {
                continue;
            }

            if (false !== stripos($buffer, '[debug]')) {
                continue;
            }

            if (false !== stripos($buffer, '[info] Pre receive Message')) {
                continue;
            }

            if (false !== stripos($buffer, '[info] Idle')) {
                continue;
            }

            return false;
        }

        return !empty($output);
    }

    /**
     * {@inheritdoc}
     */
    public function isOutdatedState()
    {
        return $this->process->isRunning();
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
            $this->killProcess();
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

    /**
     * @inheritdoc
     */
    public function getProcess()
    {
        return $this->process;
    }
}
