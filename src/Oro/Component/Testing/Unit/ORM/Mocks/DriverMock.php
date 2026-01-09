<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * This class is a clone of namespace Doctrine\Tests\Mocks\DriverMock that is excluded from doctrine package since v2.4.
 */
class DriverMock implements Driver
{
    private $platformMock;

    private $schemaManagerMock;

    private $driverConnectionMock;

    #[\Override]
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return $this->getDriverConnection();
    }

    /**
     * Constructs the Sqlite PDO DSN.
     *
     * @return string  The DSN.
     * @override
     */
    // phpcs:disable
    protected function _constructPdoDsn(array $params)
    {
        // phpcs:enable
        return "";
    }

    /**
     * @override
     */
    #[\Override]
    public function getDatabasePlatform()
    {
        if (!$this->platformMock) {
            $this->platformMock = new DatabasePlatformMock();
        }
        return $this->platformMock;
    }

    /**
     * @param \Doctrine\DBAL\Connection $conn
     * @param AbstractPlatform $platform
     * @override
     */
    #[\Override]
    public function getSchemaManager(\Doctrine\DBAL\Connection $conn, AbstractPlatform $platform)
    {
        if ($this->schemaManagerMock == null) {
            return new SchemaManagerMock($conn);
        } else {
            return $this->schemaManagerMock;
        }
    }

    /* MOCK API */

    public function setDatabasePlatform(\Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        $this->platformMock = $platform;
    }

    public function setSchemaManager(AbstractSchemaManager $sm)
    {
        $this->schemaManagerMock = $sm;
    }

    public function getDriverConnection()
    {
        if ($this->driverConnectionMock == null) {
            return new DriverConnectionMock();
        } else {
            return $this->driverConnectionMock;
        }
    }

    public function setDriverConnection(Connection $dc)
    {
        $this->driverConnectionMock = $dc;
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return new \Doctrine\DBAL\Driver\API\PostgreSQL\ExceptionConverter();
    }
}
