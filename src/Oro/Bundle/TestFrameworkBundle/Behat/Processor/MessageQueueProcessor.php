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
    const CONSUMERS_AMOUNT = 1;

    /** @var KernelInterface */
    private $kernel;

    /** @var Process[] */
    private $processes = [];

    /** @var \DateTime */
    private $lastCacheStateChangeDate;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->lastCacheStateChangeDate = new \DateTime('now', new \DateTimeZone('UTC'));
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

                $process->setTimeout(null);
                $process->start(function ($type, $buffer) use ($filesystem) {
                    if (Process::ERR === $type) {
                        $this->getLogger()->error($buffer);
                    } else {
                        $filesystem->appendToFile(sprintf('%s/mq.log', realpath($this->kernel->getLogDir())), $buffer);
                    }
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
            $process->stop(1);
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
            if (null === $cacheChangeDate || $cacheChangeDate > $this->lastCacheStateChangeDate) {
                $this->lastCacheStateChangeDate = $cacheChangeDate;
                $this->stopMessageQueue();
                $this->startMessageQueue();
            } else {
                throw new \RuntimeException('Message Queue is not running');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cleanUp()
    {
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
