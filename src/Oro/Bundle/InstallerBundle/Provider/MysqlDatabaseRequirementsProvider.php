<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Provider;

use Doctrine\DBAL\Connection;
use Oro\Bundle\InstallerBundle\Enum\DatabasePlatform;
use Oro\Component\DoctrineUtils\DBAL\DbPrivilegesProvider;

/**
 * MySQL database requirements provider
 */
class MysqlDatabaseRequirementsProvider extends AbstractDatabaseRequirementsProvider
{
    public const REQUIRED_VERSION = '8.0';

    /**
     * @inheritDoc
     */
    protected function getTargetPlatformName(): string
    {
        return DatabasePlatform::MYSQL;
    }

    /**
     * @inheritDoc
     */
    protected function getTargetPlatformLabel(): string
    {
        return 'MySQL';
    }

    /**
     * @inheritDoc
     */
    protected function getRequiredPlatformVersion(): string
    {
        return self::REQUIRED_VERSION;
    }

    /**
     * @inheritDoc
     */
    protected function getRequiredPrivileges(): array
    {
        return ['INSERT', 'SELECT', 'UPDATE', 'DELETE', 'REFERENCES', 'TRIGGER', 'CREATE', 'DROP'];
    }

    /**
     * @inheritDoc
     */
    protected function getGrantedPrivileges(Connection $connection): array
    {
        return DbPrivilegesProvider::getMySqlGrantedPrivileges(
            $connection->getWrappedConnection(),
            $connection->getDatabase()
        );
    }
}
