<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Tests\Unit\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\InstallerBundle\Enum\DatabasePlatform;
use Oro\Bundle\InstallerBundle\Provider\MysqlDatabaseRequirementsProvider;
use Oro\Component\Testing\Unit\ORM\Mocks\ConnectionMock;
use Oro\Component\Testing\Unit\ORM\Mocks\DatabasePlatformMock;
use Oro\Component\Testing\Unit\ORM\Mocks\DriverMock;
use PHPUnit\Framework\TestCase;

class MysqlDatabaseRequirementsProviderTest extends TestCase
{
    public function testCollectionSize(): void
    {
        $provider = $this->getProvider($this->getDoctrine($this->getConnection()), []);

        $requirements = $provider->getOroRequirements();

        $this->assertNotNull($requirements);

        $collection = $requirements->all();
        $this->assertCount(2, $collection);
    }

    public function testRequiredVersionFulfilled(): void
    {
        $provider = $this->getProvider($this->getDoctrine($this->getConnection()), []);

        $requirements = $provider->getOroRequirements()->all();

        $requirement = $requirements[0];
        $this->assertTrue($requirement->isFulfilled());
    }

    public function testRequiredVersionNotFulfilled(): void
    {
        $provider = $this->getProvider($this->getDoctrine($this->getConnection('1.0')), []);

        $requirements = $provider->getOroRequirements()->all();

        $requirement = $requirements[0];
        $this->assertFalse($requirement->isFulfilled());
    }

    public function testRequiredPrivilegesIsGranted(): void
    {
        $provider = $this->getProvider($this->getDoctrine($this->getConnection()), $this->getRequiredPrivileges());

        $requirements = $provider->getOroRequirements()->all();

        $requirement = $requirements[1];
        $this->assertTrue($requirement->isFulfilled());
    }

    public function testRequiredPrivilegesIsNotGranted(): void
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

        $driverConnection = new class ($version) {
            public function __construct(private string $serverVersion)
            {
            }

            public function getServerVersion(): string
            {
                return $this->serverVersion;
            }
        };

        $connection = new class ([], new DriverMock(), $driverConnection) extends ConnectionMock {
            private object $wrappedConnection;

            public function __construct(array $params, DriverMock $driver, object $wrappedConnection)
            {
                parent::__construct($params, $driver);
                $this->wrappedConnection = $wrappedConnection;
            }

            public function getWrappedConnection(): object
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
        return new class ($doctrine, $grantedPrivileges) extends MysqlDatabaseRequirementsProvider {
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
