<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Processor;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\RuntimeException;
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

    /**
     * Maximum number of retries to start a consumer.
     */
    private const MAX_START_RETRIES = 3;

    private ?KernelInterface $kernel = null;

    /** @var Process[] */
    private array $processes = [];

    private ?\DateTimeInterface $lastCacheStateChangeDate = null;

    private int $retries = 0;

    private int $startRetries = 0;

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
        // lazy loading, allows starting MQ without status control

        if (!$this->processes) {
            for ($i = 1; $i <= self::CONSUMERS_AMOUNT; $i++) {
                $this->processes[] = $this->startConsumerProcess();
            }
        }
    }

    protected function startConsumerProcess(): Process
    {
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

        $logDir = realpath($this->kernel->getLogDir());

        $process = new Process($command);
        $process->setTimeout(self::TIMEOUT);
        $process->setIdleTimeout(self::IDLE_TIMEOUT);

        $this->getLogger()->warning(
            'Starting consumer with the command: {command}',
            ['command' => $process->getCommandLine()]
        );

        $process->start(function ($type, $buffer) use ($filesystem, $logDir) {
            if (Process::ERR === $type) {
                $this->getLogger()->error($buffer);
            }

            $filesystem->appendToFile(sprintf('%s/mq.log', $logDir), $buffer);
        });

        try {
            $process->waitUntil(function ($type, $buffer) use ($filesystem, $logDir) {
                $isError = Process::ERR === $type;
                if ($isError) {
                    $this->getLogger()->error($buffer);
                }

                $filesystem->appendToFile(sprintf('%s/mq.log', $logDir), $buffer);

                return true;
            });
        } catch (RuntimeException $exception) {
            $this->getLogger()->error(
                'Failed starting a message queue consumer: {message}',
                ['message' => $exception->getMessage(), 'exception' => $exception]
            );

            ++$this->startRetries;

            if ($this->startRetries > self::MAX_START_RETRIES) {
                throw new \RuntimeException('Failed starting a message queue consumer');
            }

            $this->getLogger()->warning(
                'Retrying to start a message queue consumer. Try #{try}',
                ['try' => $this->startRetries]
            );

            return $this->startConsumerProcess();
        }

        $this->startRetries = 0;

        return $process;
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
        return $this->kernel->getContainer()->get('monolog.logger.consumer');
    }
}
