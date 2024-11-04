<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Behat tests kernel isolator.
 */
class KernelIsolator implements IsolatorInterface
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    #[\Override]
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Booting the Kernel</info>');
        $this->kernel->shutdown();
        $this->kernel->boot();
    }

    #[\Override]
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
        $this->kernel->shutdown();
        $this->kernel->boot();
    }

    #[\Override]
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $this->kernel->shutdown();
        $this->kernel->boot();
    }

    #[\Override]
    public function terminate(AfterFinishTestsEvent $event)
    {
        $this->kernel->shutdown();
    }

    #[\Override]
    public function isApplicable(ContainerInterface $container)
    {
        return true;
    }

    #[\Override]
    public function isOutdatedState()
    {
        return false;
    }

    #[\Override]
    public function restoreState(RestoreStateEvent $event)
    {
    }

    #[\Override]
    public function getName()
    {
        return 'Kernel';
    }

    #[\Override]
    public function getTag()
    {
        return 'kernel';
    }
}
