<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpKernel\Profiler\Profiler;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\PlatformBundle\Maintenance\Mode as MaintenanceMode;

class EntityProcessor
{
    /** @var MaintenanceMode */
    protected $maintenance;

    /** @var CommandExecutor */
    protected $commandExecutor;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Profiler */
    protected $profiler;

    /**
     * @param MaintenanceMode $maintenance
     * @param CommandExecutor $commandExecutor
     * @param LoggerInterface $logger
     * @param Profiler        $profiler
     */
    public function __construct(
        MaintenanceMode $maintenance,
        CommandExecutor $commandExecutor,
        LoggerInterface $logger,
        Profiler $profiler = null
    ) {
        $this->maintenance     = $maintenance;
        $this->commandExecutor = $commandExecutor;
        $this->logger          = $logger;
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
     */
    public function updateDatabase($warmUpConfigCache = false, $updateRouting = false)
    {
        set_time_limit(0);

        // disable Profiler to avoid an exception in DoctrineDataCollector
        // in case if entity classes and Doctrine metadata are not match each other in the current process
        if ($this->profiler) {
            $this->profiler->disable();
        }

        $this->maintenance->activate();

        $commands = [
            'oro:entity-extend:update-config' => [
                '--update-custom' => true,
            ],
            'oro:entity-extend:cache:warmup'  => [],
            'oro:entity-extend:update-schema' => []
        ];
        if ($warmUpConfigCache) {
            $commands = array_merge(
                $commands,
                [
                    'oro:entity-config:cache:warmup' => []
                ]
            );
        }
        if ($updateRouting) {
            $commands = array_merge(
                $commands,
                [
                    'router:cache:clear'  => [],
                    'fos:js-routing:dump' => []
                ]
            );
        }

        return $this->executeCommands($commands);
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
