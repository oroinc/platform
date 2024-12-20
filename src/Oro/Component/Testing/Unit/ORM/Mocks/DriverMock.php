<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

/**
 * This class is a clone of namespace Doctrine\Tests\Mocks\DriverMock that is excluded from doctrine package since v2.4.
 */
class DriverMock implements \Doctrine\DBAL\Driver
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
    // @codingStandardsIgnoreStart
    protected function _constructPdoDsn(array $params)
    {
        // @codingStandardsIgnoreEnd
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
     * @override
     */
    #[\Override]
    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
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

    public function setSchemaManager(\Doctrine\DBAL\Schema\AbstractSchemaManager $sm)
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

    public function setDriverConnection(\Doctrine\DBAL\Driver\Connection $dc)
    {
        $this->driverConnectionMock = $dc;
    }

    #[\Override]
    public function getName()
    {
        return 'mock';
    }

    #[\Override]
    public function getDatabase(\Doctrine\DBAL\Connection $conn)
    {
        return;
    }
}
