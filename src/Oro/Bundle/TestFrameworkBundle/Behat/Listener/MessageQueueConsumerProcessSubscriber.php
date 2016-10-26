<?php
namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class MessageQueueConsumerProcessSubscriber implements EventSubscriberInterface
{
    /**
     * @var Process
     */
    private $process;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $command = sprintf(
            './console oro:message-queue:consume --env=%s %s',
            $kernel->getEnvironment(),
            $kernel->isDebug() ? '' : '--no-debug'
        );

        $this->process = new Process($command, $kernel->getRootDir());
    }

    public function stopMessageConsumer()
    {
        $this->process->stop();

        //Process::stop() don't stop subprocesses see https://github.com/symfony/symfony/issues/5030
        try {
            $process = new Process('kill -9 `pgrep -f oro:message-queue:consume`');
            $process->run();
        } catch (RuntimeException $e) {
        }
    }

    public function startMessageConsumer()
    {
        /** Cunsumer is a demon so we need to run it asynchronously */
        $this->process->start();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // must be after FeatureIsolationSubscriber::beforeFeature
            BeforeFeatureTested::BEFORE  => ['startMessageConsumer', 90],
            // must be before FeatureIsolationSubscriber::afterFeature
            AfterFeatureTested::AFTER  => ['stopMessageConsumer', -90],
        ];
    }
}
