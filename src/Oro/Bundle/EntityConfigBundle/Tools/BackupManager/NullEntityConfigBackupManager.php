<?php

namespace Oro\Bundle\EntityConfigBundle\Tools\BackupManager;

/**
 * Default implementation of EntityConfigBackupManagerInterface
 */
class NullEntityConfigBackupManager implements EntityConfigBackupManagerInterface
{
    #[\Override]
    public function isEnabled(): bool
    {
        return false;
    }

    #[\Override]
    public function makeBackup(): void
    {
        throw new \BadMethodCallException('Not implemented');
    }

    #[\Override]
    public function restoreFromBackup(): void
    {
        throw new \BadMethodCallException('Not implemented');
    }

    #[\Override]
    public function dropBackup(): void
    {
        throw new \BadMethodCallException('Not implemented');
    }
}
