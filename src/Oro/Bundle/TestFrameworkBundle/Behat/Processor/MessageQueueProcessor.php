<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Processor;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Base message queue processor that control message queue consumer during test execution
 */
class MessageQueueProcessor implements MessageQueueProcessorInterface
{
    private const CONSUMERS_AMOUNT = 1;

    /**
     * Maximum number of retries to start consuming after a failure.
     */
    private const MAX_RETRIES = 20;

    /** @var KernelInterface */
    private $kernel;

    /** @var Process[] */
    private $processes = [];

    /** @var \DateTime */
    private $lastCacheStateChangeDate;

    /** @var int */
    private $retries = 0;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc]
     */
    public function startMessageQueue()
    {
        // start processes if they are not initialized
        // lazy loading, allows to start MQ without status control
        if (!$this->processes) {
            /** @var Filesystem $filesystem */
            $filesystem = $this->kernel->getContainer()->get('filesystem');
            $phpBinaryFinder = new PhpExecutableFinder();
            $phpBinaryPath = $phpBinaryFinder->find();

            $command = [
                $phpBinaryPath,
                sprintf('%s/console', realpath($this->kernel->getProjectDir()) . '/bin'),
                'oro:message-queue:consume',
                '-vv',
                sprintf('--env=%s', $this->kernel->getEnvironment())
            ];

            for ($i = 1; $i <= self::CONSUMERS_AMOUNT; $i++) {
                $process = new Process($command);

                $process->setTimeout(self::TIMEOUT);
                $process->setIdleTimeout(self::TIMEOUT);
                $process->start(function ($type, $buffer) use ($filesystem) {
                    if (Process::ERR === $type) {
                        $this->getLogger()->error($buffer);
                    }

                    $filesystem->appendToFile(sprintf('%s/mq.log', realpath($this->kernel->getLogDir())), $buffer);
                });

                $this->processes[] = $process;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopMessageQueue()
    {
        foreach ($this->processes as $process) {
            $process->stop();
        }

        $this->processes = [];
    }

    /**
     * {@inheritdoc}
     */
    public function waitWhileProcessingMessages($timeLimit = self::TIMEOUT)
    {
        $container = $this->kernel->getContainer();
        $cacheState = $container->get('oro_message_queue.consumption.cache_state');

        $isRunning = $this->isRunning();
        if (!$isRunning) {
            $cacheChangeDate = $cacheState->getChangeDate();
            if ($cacheChangeDate > $this->lastCacheStateChangeDate) {
                $this->lastCacheStateChangeDate = $cacheChangeDate;
            } else {
                ++$this->retries;
            }

            if ($this->retries >= self::MAX_RETRIES) {
                throw new \RuntimeException('Message Queue is not running');
            }

            $this->stopMessageQueue();
            $this->startMessageQueue();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cleanUp()
    {
        $this->stopMessageQueue();

        $this->retries = 0;
        $this->lastCacheStateChangeDate = null;
        $this->processes = [];
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger()
    {
        return $this->kernel->getContainer()->get('logger');
    }
}
