<?php

namespace Oro\Bundle\EntityConfigBundle\Tools\BackupManager;

/**
 * Default implementation of EntityConfigBackupManagerInterface
 */
class NullEntityConfigBackupManager implements EntityConfigBackupManagerInterface
{
    /**
     * {@inheritdoc}
     */
    public function isEnabled(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function makeBackup(): void
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function restoreFromBackup(): void
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function dropBackup(): void
    {
        throw new \BadMethodCallException('Not implemented');
    }
}
