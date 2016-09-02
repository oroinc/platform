<?php
namespace Oro\Component\MessageQueue\Transport\Dbal;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

/**
 * We need the lazy connection to avoid
 * 'Circular reference detected for service "doctrine.dbal.default_connection"' issues
 */
class DbalLazyConnection extends DbalConnection
{
    /**
     * @var bool
     */
    private $isInit;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var array
     */
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

        $this->isInit = false;
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalSession
     */
    public function createSession()
    {
        return parent::createSession();
    }

    /**
     * @return Connection
     */
    public function getDBALConnection()
    {
        $this->init();

        return parent::getDBALConnection();
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->isInit) {
            parent::close();
        }
    }

    private function init()
    {
        if ($this->isInit) {
            return;
        }

        parent::__construct(
            $this->registry->getConnection($this->connectionName),
            $this->tableName,
            $this->options
        );

        $this->isInit = true;
    }
}
