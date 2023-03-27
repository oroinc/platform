<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tools\BackupManager\EntityConfigBackupManagerInterface;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityExtendBundle\Event\UpdateSchemaEvent;
use Oro\Bundle\MaintenanceBundle\Maintenance\Mode as MaintenanceMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * Provides a way to update the database schema and all related caches to reflect changes made in extended entities.
 */
class EntityExtendUpdateProcessor
{
    public function __construct(
        private MaintenanceMode $maintenance,
        private CommandExecutor $commandExecutor,
        private LoggerInterface $logger,
        private EventDispatcherInterface $dispatcher,
        protected EntityConfigBackupManagerInterface $entityConfigBackupManager,
        protected ConfigManager $configManager,
        private ?Profiler $profiler = null
    ) {
    }

    /**
     * Updates the database schema and all related caches to reflect changes made in extended entities.
     */
    public function processUpdate(): EntityExtendUpdateProcessorResult
    {
        set_time_limit(0);

        // disable Profiler to avoid an exception in DoctrineDataCollector
        // in case if entity classes and Doctrine metadata are not match each other in the current process
        if ($this->profiler) {
            $this->profiler->disable();
        }

        try {
            $this->maintenance->activate();
            if ($this->entityConfigBackupManager->isEnabled()) {
                $this->entityConfigBackupManager->makeBackup();
                try {
                    $this->applyChangesToDatabase();
                } catch (\Throwable $e) {
                    $this->entityConfigBackupManager->restoreFromBackup();
                    $this->executeCommand('oro:entity-extend:cache:clear');

                    $this->logger->error(
                        'Failed to update the database schema!' .
                        ' All changes in the schema were reverted and cache was cleared.',
                        [
                            'exception' => $e
                        ]
                    );

                    return new EntityExtendUpdateProcessorResult(
                        false,
                        'Failed to update the database schema!' .
                        ' All changes in the schema were reverted and cache was cleared. Details you can find in log.',
                    );
                }
                $this->entityConfigBackupManager->dropBackup();
            } else {
                $this->applyChangesToDatabase();
            }

            /**
             * Process enum sync without wrapping in transactional flow,
             * because this changes won't break the system on fail
             */
            $this->executeCommand('oro:entity-extend:update-schema');
            $this->executeCommand('oro:entity-config:cache:warmup');
            $this->executeCommand('validator:cache:clear');
            $this->executeCommand('router:cache:clear');
            $this->executeCommand('fos:js-routing:dump');
            $this->dispatchUpdateSchemaEvent();
        } catch (\RuntimeException $e) {
            $this->logger->error(
                'Failed to update the database schema'
                . ' and all related caches to reflect changes made in extended entities.',
                ['exception' => $e]
            );

            return new EntityExtendUpdateProcessorResult(
                false,
                'Failed to update the database schema'
                . ' and all related caches to reflect changes made in extended entities. Exception message: ' .
                $e->getMessage()
            );
        }

        return new EntityExtendUpdateProcessorResult(true);
    }

    protected function applyChangesToDatabase(): void
    {
        // Before running oro:entity-extend:update-config, the model cache must be cleared.
        $this->configManager->clearModelCache();
        $this->executeCommand('oro:entity-extend:update-config', ['--update-custom' => true, '--force' => true]);
        $this->executeCommand('oro:entity-extend:cache:warmup');
        $this->executeCommand('oro:entity-extend:update-schema', [
            '--skip-enum-sync' => true
        ]);
    }

    private function executeCommand(string $command, array $options = []): void
    {
        try {
            $this->commandExecutor->runCommand($command, $options, $this->logger);
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf('The command "%s" failed. Reason: %s', $command, $e->getMessage()));
        }
    }

    private function dispatchUpdateSchemaEvent(): void
    {
        try {
            $this->dispatcher->dispatch(
                new UpdateSchemaEvent($this->commandExecutor, $this->logger),
                UpdateSchemaEvent::NAME
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
