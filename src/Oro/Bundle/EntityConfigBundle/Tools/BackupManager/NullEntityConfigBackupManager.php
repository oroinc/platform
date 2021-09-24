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
    public function isEnabled()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function makeBackup()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function restoreFromBackup()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function dropBackup()
    {
        throw new \BadMethodCallException('Not implemented');
    }
}
