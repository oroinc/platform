<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\Console\Helper\QuestionHelper;
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
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Starting message queue command</info>');
        $this->process->start();
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
        $this->process->start();
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        var_dump($this->process->isSuccessful());
        $this->process->stop();
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
        $this->process->stop();
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return 'dbal' === $container->getParameter('message_queue_transport');
    }

    /**
     * {@inheritdoc}
     */
    public function isOutdatedState()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function restoreState(RestoreStateEvent $event)
    {}

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Dbal Message Queue';
    }
}