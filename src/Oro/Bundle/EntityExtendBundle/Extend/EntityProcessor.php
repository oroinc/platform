<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

use Psr\Log\LoggerInterface;

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

    /**
     * @var array
     *
     * Disable sync caches for doctrine related commands
     * because in other case entity classes and Doctrine metadata may not match each other in the current process
     * and as result DoctrineDataCollector raises an exception
     */
    protected $commands = [
        'oro:entity-extend:update-config' => ['--disable-cache-sync' => true],
        'oro:entity-extend:cache:warmup'  => ['--disable-cache-sync' => true],
        'oro:entity-extend:update-schema' => ['--disable-cache-sync' => true],
        'router:cache:clear'              => [],
        'fos:js-routing:dump'             => ['--target' => 'web/js/routes.js']
    ];

    /**
     * @param MaintenanceMode $maintenance
     * @param CommandExecutor $commandExecutor
     * @param LoggerInterface $logger
     */
    public function __construct(
        MaintenanceMode $maintenance,
        CommandExecutor $commandExecutor,
        LoggerInterface $logger
    ) {
        $this->maintenance     = $maintenance;
        $this->commandExecutor = $commandExecutor;
        $this->logger          = $logger;
    }

    /**
     * Updates database and all related caches according to changes of extended entities
     *
     * @return bool
     */
    public function updateDatabase()
    {
        set_time_limit(0);

        $this->maintenance->activate();

        $isSuccess = $this->executeCommands($this->commands);

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
