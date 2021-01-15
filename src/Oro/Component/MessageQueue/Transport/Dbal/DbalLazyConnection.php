<?php
namespace Oro\Component\MessageQueue\Transport\Dbal;

use Doctrine\Persistence\ManagerRegistry;

/**
 * We need the lazy connection to avoid
 * 'Circular reference detected for service "doctrine.dbal.default_connection"' issues
 */
class DbalLazyConnection extends DbalConnection
{
    /** @var bool */
    private $isInitialized;

    /** @var ManagerRegistry */
    private $registry;

    /** @var string */
    private $connectionName;

    /** @var string */
    private $tableName;

    /** @var array */
    private $options;

    /**
     * @param ManagerRegistry $registry
     * @param string $connectionName
     * @param string $tableName
     * @param array $options
     */
    public function __construct(ManagerRegistry $registry, $connectionName, $tableName, array $options = [])
    {
        // the parent::__construct method is not called on purpose.

        $this->registry = $registry;
        $this->connectionName = $connectionName;
        $this->tableName = $tableName;
        $this->options = $options;

        $this->isInitialized = false;
    }

    public function createSession()
    {
        return parent::createSession();
    }

    public function getDBALConnection()
    {
        $this->initialize();

        return parent::getDBALConnection();
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function close()
    {
        if ($this->isInitialized) {
            parent::close();
        }
    }

    private function initialize()
    {
        if ($this->isInitialized) {
            return;
        }

        parent::__construct(
            $this->registry->getConnection($this->connectionName),
            $this->tableName,
            $this->options
        );

        $this->isInitialized = true;
    }
}
