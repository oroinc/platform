<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

use Oro\Bundle\EntityConfigBundle\Tools\BackupManager\EntityConfigBackupManagerInterface;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityExtendBundle\Event\UpdateSchemaEvent;
use Oro\Bundle\PlatformBundle\Maintenance\Mode as MaintenanceMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

/**
 * Provides a way to update the database schema and all related caches to reflect changes made in extended entities.
 */
class EntityExtendUpdateProcessor
{
    /** @var MaintenanceMode */
    private $maintenance;

    /** @var CommandExecutor */
    private $commandExecutor;

    /** @var LoggerInterface */
    private $logger;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var Profiler|null */
    private $profiler;

    /** @var EntityConfigBackupManagerInterface */
    protected $entityConfigBackupManager;

    public function __construct(
        MaintenanceMode $maintenance,
        CommandExecutor $commandExecutor,
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        Profiler $profiler = null
    ) {
        $this->maintenance = $maintenance;
        $this->commandExecutor = $commandExecutor;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->profiler = $profiler;
    }

    /**
     * @param EntityConfigBackupManagerInterface $entityConfigBackupManager
     */
    public function setEntityConfigBackupManager(EntityConfigBackupManagerInterface $entityConfigBackupManager)
    {
        $this->entityConfigBackupManager = $entityConfigBackupManager;
    }

    /**
     * Updates the database schema and all related caches to reflect changes made in extended entities.
     */
    public function processUpdate(): bool
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
                        ' All changes in the schema were reverted and cache cleared.',
                        [
                            'exception' => $e
                        ]
                    );

                    return false;
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

            return false;
        }

        return true;
    }

    protected function applyChangesToDatabase(): void
    {
        $this->executeCommand('oro:entity-extend:update-config', ['--update-custom' => true]);
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
