<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\InstallerBundle\Enum\DatabasePlatform;
use Oro\Bundle\InstallerBundle\Provider\MysqlDatabaseRequirementsProvider;
use Oro\Component\TestUtils\ORM\Mocks\ConnectionMock;
use Oro\Component\TestUtils\ORM\Mocks\DatabasePlatformMock;
use Oro\Component\TestUtils\ORM\Mocks\DriverMock;
use PHPUnit\Framework\TestCase;

class MysqlDatabaseRequirementsProviderTest extends TestCase
{
    public function testCollectionSize()
    {
        $connection = $this->getConnectionMock();
        $registry = $this->getManagerRegistryMock($connection);
        $provider = $this->getProvider($registry, []);

        $requirements = $provider->getOroRequirements();

        $this->assertNotNull($requirements);

        $collection = $requirements->all();
        $this->assertCount(2, $collection);
    }

    public function testRequiredVersionFulfilled()
    {
        $connection = $this->getConnectionMock();
        $registry = $this->getManagerRegistryMock($connection);
        $provider = $this->getProvider($registry, []);

        $requirements = $provider->getOroRequirements()->all();

        $requirement = $requirements[0];
        $this->assertTrue($requirement->isFulfilled());
    }

    public function testRequiredVersionNotFulfilled()
    {
        $connection = $this->getConnectionMock('1.0');
        $registry = $this->getManagerRegistryMock($connection);
        $provider = $this->getProvider($registry, []);

        $requirements = $provider->getOroRequirements()->all();

        $requirement = $requirements[0];
        $this->assertFalse($requirement->isFulfilled());
    }

    public function testRequiredPrivilegesIsGranted()
    {
        $connection = $this->getConnectionMock();
        $registry = $this->getManagerRegistryMock($connection);
        $provider = $this->getProvider($registry, $this->getRequiredPrivileges());

        $requirements = $provider->getOroRequirements()->all();

        $requirement = $requirements[1];
        $this->assertTrue($requirement->isFulfilled());
    }

    public function testRequiredPrivilegesIsNotGranted()
    {
        $connection = $this->getConnectionMock();
        $registry = $this->getManagerRegistryMock($connection);
        $provider = $this->getProvider($registry, []);

        $requirements = $provider->getOroRequirements()->all();

        $requirement = $requirements[1];
        $this->assertFalse($requirement->isFulfilled());
    }

    protected function getManagerRegistryMock(ConnectionMock $connectionMock): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getConnectionNames')->willReturn(['default' => 'doctrine.dbal.default_connection']);
        $registry->method('getConnections')->willReturn(['default' => $connectionMock]);

        return $registry;
    }

    protected function getConnectionMock(
        string $version = MysqlDatabaseRequirementsProvider::REQUIRED_VERSION
    ): ConnectionMock {
        $connection = new class([], new DriverMock(), null, null, $version) extends ConnectionMock {
            protected string $version;

            public function __construct(
                array $params,
                $driver,
                $config = null,
                $eventManager = null,
                string $version = '1.0'
            ) {
                parent::__construct($params, $driver, $config, $eventManager);

                $this->version = $version;
            }

            public function getWrappedConnection()
            {
                return $this;
            }

            public function getServerVersion(): string
            {
                return $this->version;
            }
        };

        $connection->setDatabasePlatform($this->getPlatformMock());

        return $connection;
    }

    protected function getPlatformMock(): DatabasePlatformMock
    {
        return new class extends DatabasePlatformMock {
            public function getName(): string
            {
                return DatabasePlatform::MYSQL;
            }
        };
    }

    protected function getRequiredPrivileges(): array
    {
        return ['INSERT', 'SELECT', 'UPDATE', 'DELETE', 'REFERENCES', 'TRIGGER', 'CREATE', 'DROP'];
    }

    protected function getProvider(
        ManagerRegistry $registryMock,
        array $grantedPrivileges = []
    ): MysqlDatabaseRequirementsProvider {
        return new class($registryMock, $grantedPrivileges) extends MysqlDatabaseRequirementsProvider {
            protected array $grantedPrivileges;

            public function __construct(ManagerRegistry $registry, array $grantedPrivileges)
            {
                parent::__construct($registry);

                $this->grantedPrivileges = $grantedPrivileges;
            }

            protected function getGrantedPrivileges(Connection $connection): array
            {
                return $this->grantedPrivileges;
            }
        };
    }
}
