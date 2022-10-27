<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Requirements\RequirementCollection;

/**
 * Abstract database requirements
 */
abstract class AbstractDatabaseRequirementsProvider extends AbstractRequirementsProvider
{
    protected ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @inheritDoc
     */
    public function getOroRequirements(): ?RequirementCollection
    {
        $collection = new RequirementCollection();

        foreach ($this->registry->getConnections() as $name => $connection) {
            $this->addDatabaseRequirements($collection, $connection, $name);
        }

        return $collection;
    }

    /**
     * Database platform name from $connection->getDatabasePlatform()->getName()
     */
    abstract protected function getTargetPlatformName(): string;

    /**
     * Platform name for messages
     */
    abstract protected function getTargetPlatformLabel(): string;

    /**
     * Required platform version
     */
    abstract protected function getRequiredPlatformVersion(): string;

    /**
     * An array of required privileges
     */
    abstract protected function getRequiredPrivileges(): array;

    /**
     * An array of granted privileges
     */
    abstract protected function getGrantedPrivileges(Connection $connection): array;

    protected function addDatabaseRequirements(
        RequirementCollection $collection,
        Connection $connection,
        string $connectionName
    ): void {
        $requiredPlatformVersion = $this->getRequiredPlatformVersion();
        $targetPlatformLabel = $this->getTargetPlatformLabel();
        $requiredPrivileges = $this->getRequiredPrivileges();

        if ($this->getCurrentPlatformName($connection) === $this->getTargetPlatformName()) {
            $platformVersion = $this->getCurrentPlatformVersion($connection) ?? 'undefined';
            $grantedPrivileges = $this->getGrantedPrivileges($connection);
            $privilegesDiff = array_diff($requiredPrivileges, $grantedPrivileges);

            $collection->addRequirement(
                version_compare($platformVersion, $requiredPlatformVersion, '>='),
                sprintf(
                    'Connection "%s": Required %s version is installed (%s)',
                    $connectionName,
                    $targetPlatformLabel,
                    $platformVersion,
                ),
                sprintf(
                    'Connection "%s": %s server version should be %s or higher, current version: %s',
                    $connectionName,
                    $targetPlatformLabel,
                    $requiredPlatformVersion,
                    $platformVersion,
                )
            );

            $collection->addRequirement(
                in_array('ALL PRIVILEGES', $grantedPrivileges) || $privilegesDiff === [],
                sprintf(
                    'Connection "%s": All required database privileges is granted ',
                    $connectionName
                ),
                sprintf(
                    'Connection "%s": Database privileges must be granted: %s',
                    $connectionName,
                    rtrim(implode(', ', $privilegesDiff), ', '),
                )
            );
        }
    }

    protected function getCurrentPlatformName(Connection $connection): ?string
    {
        try {
            return $connection->getDatabasePlatform()->getName();
        } catch (Exception $exception) {
            return null;
        }
    }

    protected function getCurrentPlatformVersion(Connection $connection): ?string
    {
        try {
            return $connection->getWrappedConnection()->getServerVersion();
        } catch (Exception $exception) {
            return null;
        }
    }
}
