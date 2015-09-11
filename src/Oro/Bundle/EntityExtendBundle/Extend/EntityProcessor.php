<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

use Psr\Log\LoggerInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\PlatformBundle\Maintenance\Mode as MaintenanceMode;
use Oro\Bundle\EntityExtendBundle\Event\ExtendSchemaUpdateEvent;

class EntityProcessor
{
    /** @var MaintenanceMode */
    protected $maintenance;

    /** @var CommandExecutor */
    protected $commandExecutor;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Profiler  */
    protected $profiler;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var array */
    protected $commands = [
        'oro:entity-extend:update-config' => [],
        'oro:entity-extend:cache:warmup'  => [],
        'oro:entity-extend:update-schema' => []
    ];

    /** @var array */
    protected $updateRoutingCommands = [
        'router:cache:clear'  => [],
        'fos:js-routing:dump' => []
    ];

    /**
     * @param MaintenanceMode          $maintenance
     * @param CommandExecutor          $commandExecutor
     * @param LoggerInterface          $logger
     * @param EventDispatcherInterface $eventDispatcher
     * @param Profiler                 $profiler
     */
    public function __construct(
        MaintenanceMode $maintenance,
        CommandExecutor $commandExecutor,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        Profiler $profiler = null
    ) {
        $this->maintenance     = $maintenance;
        $this->commandExecutor = $commandExecutor;
        $this->logger          = $logger;
        $this->profiler        = $profiler;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Updates database and all related caches according to changes of extended entities
     *
     * @param bool $updateRouting
     *
     * @return bool
     */
    public function updateDatabase($updateRouting = false)
    {
        set_time_limit(0);

        // disable Profiler to avoid an exception in DoctrineDataCollector
        // in case if entity classes and Doctrine metadata are not match each other in the current process
        if ($this->profiler) {
            $this->profiler->disable();
        }

        $this->maintenance->activate();

        $isSuccess = $this->executeCommands($this->commands);
        if ($isSuccess) {
            if ($updateRouting) {
                $isSuccess = $this->executeCommands($this->updateRoutingCommands);
            }

            $event = new ExtendSchemaUpdateEvent($updateRouting);
            $this->eventDispatcher->dispatch(ExtendSchemaUpdateEvent::NAME, $event);
        }

        return $isSuccess;
    }

    /**
     * @param array $commands
     *
     * @return bool
     */
    protected function executeCommands(array $commands)
    {
        $exitCode = 0;
        foreach ($commands as $command => $options) {
            $code = $this->commandExecutor->runCommand(
                $command,
                $options,
                $this->logger
            );

            if ($code !== 0) {
                $exitCode = $code;
                break;
            }
        }

        return $exitCode === 0;
    }
}
