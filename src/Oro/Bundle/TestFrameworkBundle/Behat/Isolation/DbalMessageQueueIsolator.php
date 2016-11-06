<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class DbalMessageQueueIsolator implements IsolatorInterface
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
            'exec ./console oro:message-queue:consume --env=%s %s',
            $kernel->getEnvironment(),
            $kernel->isDebug() ? '' : '--no-debug'
        );

        $this->process = new Process($command, $kernel->getRootDir());
    }

    /** {@inheritdoc} */
    public function start()
    {
        $this->process->start();
    }

    /** {@inheritdoc} */
    public function beforeTest()
    {
        $this->process->start();
    }

    /** {@inheritdoc} */
    public function afterTest()
    {
        $this->process->stop();
    }

    /** {@inheritdoc} */
    public function terminate()
    {
        $this->process->stop();
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return 'dbal' === $container->getParameter('message_queue_transport');
    }
}