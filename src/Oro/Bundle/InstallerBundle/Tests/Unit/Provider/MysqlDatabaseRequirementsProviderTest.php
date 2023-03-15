<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\Connection as DriverConnection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\InstallerBundle\Enum\DatabasePlatform;
use Oro\Bundle\InstallerBundle\Provider\MysqlDatabaseRequirementsProvider;
use Oro\Component\Testing\Unit\ORM\Mocks\ConnectionMock;
use Oro\Component\Testing\Unit\ORM\Mocks\DatabasePlatformMock;
use Oro\Component\Testing\Unit\ORM\Mocks\DriverMock;

class MysqlDatabaseRequirementsProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testCollectionSize()
    {
        $provider = $this->getProvider($this->getDoctrine($this->getConnection()), []);

        $requirements = $provider->getOroRequirements();

        $this->assertNotNull($requirements);

        $collection = $requirements->all();
        $this->assertCount(2, $collection);
    }

    public function testRequiredVersionFulfilled()
    {
        $provider = $this->getProvider($this->getDoctrine($this->getConnection()), []);

        $requirements = $provider->getOroRequirements()->all();

        $requirement = $requirements[0];
        $this->assertTrue($requirement->isFulfilled());
    }

    public function testRequiredVersionNotFulfilled()
    {
        $provider = $this->getProvider($this->getDoctrine($this->getConnection('1.0')), []);

        $requirements = $provider->getOroRequirements()->all();

        $requirement = $requirements[0];
        $this->assertFalse($requirement->isFulfilled());
    }

    public function testRequiredPrivilegesIsGranted()
    {
        $provider = $this->getProvider($this->getDoctrine($this->getConnection()), $this->getRequiredPrivileges());

        $requirements = $provider->getOroRequirements()->all();

        $requirement = $requirements[1];
        $this->assertTrue($requirement->isFulfilled());
    }

    public function testRequiredPrivilegesIsNotGranted()
    {
        $provider = $this->getProvider($this->getDoctrine($this->getConnection()), []);

        $requirements = $provider->getOroRequirements()->all();

        $requirement = $requirements[1];
        $this->assertFalse($requirement->isFulfilled());
    }

    protected function getDoctrine(ConnectionMock $connection): ManagerRegistry
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getConnectionNames')
            ->willReturn(['default' => 'doctrine.dbal.default_connection']);
        $doctrine->expects(self::any())
            ->method('getConnections')
            ->willReturn(['default' => $connection]);

        return $doctrine;
    }

    protected function getConnection(
        string $version = MysqlDatabaseRequirementsProvider::REQUIRED_VERSION
    ): ConnectionMock {
        $platform = new DatabasePlatformMock();
        $platform->setName(DatabasePlatform::MYSQL);

        $driverConnection = $this->createMock(DriverConnection::class);
        $driverConnection->expects(self::any())
            ->method('getServerVersion')
            ->willReturn($version);

        $connection = new class([], new DriverMock(), $driverConnection) extends ConnectionMock {
            private DriverConnection $wrappedConnection;

            public function __construct(array $params, DriverMock $driver, DriverConnection $wrappedConnection)
            {
                parent::__construct($params, $driver);
                $this->wrappedConnection = $wrappedConnection;
            }

            public function getWrappedConnection(): DriverConnection
            {
                return $this->wrappedConnection;
            }
        };
        $connection->setDatabasePlatform($platform);

        return $connection;
    }

    protected function getRequiredPrivileges(): array
    {
        return ['INSERT', 'SELECT', 'UPDATE', 'DELETE', 'REFERENCES', 'TRIGGER', 'CREATE', 'DROP'];
    }

    protected function getProvider(
        ManagerRegistry $doctrine,
        array $grantedPrivileges = []
    ): MysqlDatabaseRequirementsProvider {
        return new class($doctrine, $grantedPrivileges) extends MysqlDatabaseRequirementsProvider {
            protected array $grantedPrivileges;

            public function __construct(ManagerRegistry $doctrine, array $grantedPrivileges)
            {
                parent::__construct($doctrine);

                $this->grantedPrivileges = $grantedPrivileges;
            }

            protected function getGrantedPrivileges(Connection $connection): array
            {
                return $this->grantedPrivileges;
            }
        };
    }
}
