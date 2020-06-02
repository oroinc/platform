<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityExtendBundle\Event\UpdateSchemaEvent;
use Oro\Bundle\PlatformBundle\Maintenance\Mode as MaintenanceMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * This Processor provide functionality to updates database and all related caches
 * according to changes of extended entities
 * @deprecated since 4.2. Use EntityExtendUpdateProcessor instead
 */
class EntityProcessor
{
    /** @var MaintenanceMode */
    protected $maintenance;

    /** @var CommandExecutor */
    protected $commandExecutor;

    /** @var LoggerInterface */
    protected $logger;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var Profiler */
    protected $profiler;

    /**
     * @param MaintenanceMode          $maintenance
     * @param CommandExecutor          $commandExecutor
     * @param LoggerInterface          $logger
     * @param EventDispatcherInterface $dispatcher
     * @param Profiler                 $profiler
     */
    public function __construct(
        MaintenanceMode $maintenance,
        CommandExecutor $commandExecutor,
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        Profiler $profiler = null
    ) {
        $this->maintenance     = $maintenance;
        $this->commandExecutor = $commandExecutor;
        $this->logger          = $logger;
        $this->dispatcher      = $dispatcher;
        $this->profiler        = $profiler;
    }

    /**
     * Updates database and all related caches according to changes of extended entities
     *
     * @param bool $warmUpConfigCache Whether the entity config cache should be warmed up
     *                                after database schema is changed
     * @param bool $updateRouting     Whether routes should be updated after database schema is changed
     *
     * @return bool
     *
     * @deprecated since 4.2. Use EntityExtendUpdateProcessor::processUpdate() instead
     */
    public function updateDatabase($warmUpConfigCache = false, $updateRouting = false)
    {
        set_time_limit(0);

        // disable Profiler to avoid an exception in DoctrineDataCollector
        // in case if entity classes and Doctrine metadata are not match each other in the current process
        if ($this->profiler) {
            $this->profiler->disable();
        }

        try {
            $this->maintenance->activate();
            $this->executeCommand('oro:entity-extend:update-config', ['--update-custom' => true]);
            $this->executeCommand('oro:entity-extend:cache:warmup');
            $this->executeCommand('oro:entity-extend:update-schema');
            if ($warmUpConfigCache) {
                $this->executeCommand('oro:entity-config:cache:warmup');
            }
            if ($updateRouting) {
                $this->executeCommand('router:cache:clear');
                $this->executeCommand('fos:js-routing:dump');
            }
            $this->dispatchUpdateSchemaEvent();
        } catch (\RuntimeException $e) {
            $this->logger->error(
                'Failed to update the database and all related caches to reflect changes made in extended entities.',
                ['exception' => $e]
            );

            return false;
        }

        return true;
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

    /**
     * @param string $command
     * @param array  $options
     */
    private function executeCommand($command, array $options = [])
    {
        try {
            $this->commandExecutor->runCommand($command, $options, $this->logger);
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf('The command "%s" failed. Reason: %s', $command, $e->getMessage()));
        }
    }

    private function dispatchUpdateSchemaEvent()
    {
        try {
            $this->dispatcher->dispatch(
                UpdateSchemaEvent::NAME,
                new UpdateSchemaEvent($this->commandExecutor, $this->logger)
            );
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                sprintf('The processing of "%s" event failed. Reason: %s', UpdateSchemaEvent::NAME, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }
}
