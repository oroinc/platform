<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Basic class for maintenance mode isolators
 */
abstract class MaintenanceModeIsolator implements IsolatorInterface
{
    private string $phpExecutablePath;

    public function __construct(private KernelInterface $kernel)
    {
        $this->phpExecutablePath = (new PhpExecutableFinder())->find();
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(ContainerInterface $container)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function restoreState(RestoreStateEvent $event)
    {
    }

    /**
     * @inheritDoc
     */
    public function terminate(AfterFinishTestsEvent $event)
    {
    }

    /**
     * @inheritDoc
     */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
    }

    /**
     * @inheritDoc
     */
    public function isOutdatedState()
    {
        return false;
    }

    protected function runCommand(string $name): void
    {
        $command = [
            $this->phpExecutablePath,
            realpath($this->kernel->getProjectDir()).DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'console',
            $name,
            '-n',
            sprintf('--env=%s', $this->kernel->getEnvironment()),
        ];

        $process = new Process($command);
        $process->mustRun();
        // Renew the cache state to force restart the consumer after enabling or disabling the maintenance mode,
        // otherwise it may go to the maintenance mode too late, because of the big delay in
        // Oro\Bundle\MessageQueueBundle\Consumption\Extension\MaintenanceExtension::$idleTime
        $this->kernel->getContainer()->get('oro_message_queue.consumption.cache_state')->renewChangeDate();
        sleep(1);
    }
}
