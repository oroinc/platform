<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * A mock class for DBAL connection.
 */
class ConnectionMock extends Connection
{
    private AbstractPlatform $platformMock;
    private mixed $fetchOneResult;
    private mixed $lastInsertId = 0;

    public function __construct(array $params, $driver, $config = null, $eventManager = null)
    {
        $this->platformMock = new DatabasePlatformMock();
        parent::__construct($params, $driver, $config, $eventManager);
    }

    /**
     * {@inheritDoc}
     */
    public function getDatabasePlatform()
    {
        return $this->platformMock;
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId($seqName = null)
    {
        return $this->lastInsertId;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchColumn($statement, array $params = [], $colnum = 0, array $types = [])
    {
        return $this->fetchOneResult;
    }

    /**
     * {@inheritDoc}
     */
    public function quote($input, $type = null)
    {
        if (is_string($input)) {
            return "'" . $input . "'";
        }

        return $input;
    }

    public function setFetchOneResult(mixed $fetchOneResult): void
    {
        $this->fetchOneResult = $fetchOneResult;
    }

    public function setDatabasePlatform(AbstractPlatform $platform): void
    {
        $this->platformMock = $platform;
    }

    public function setLastInsertId(mixed $id): void
    {
        $this->lastInsertId = $id;
    }

    public function reset(): void
    {
        $this->lastInsertId = 0;
    }
}
