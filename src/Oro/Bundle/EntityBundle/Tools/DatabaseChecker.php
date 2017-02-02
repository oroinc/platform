<?php

namespace Oro\Bundle\EntityBundle\Tools;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

class DatabaseChecker
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var string[] */
    private $requiredTables;

    /** @var bool */
    private $installed;

    /** @var bool */
    private $dbCheck;

    /**
     * @param ManagerRegistry $doctrine       The instance of Doctrine ManagerRegistry object
     * @param string[]        $requiredTables The list of tables that should exist in the database
     * @param mixed           $installed      The flag indicates that the application is already installed
     */
    public function __construct(ManagerRegistry $doctrine, array $requiredTables, $installed)
    {
        $this->doctrine = $doctrine;
        $this->requiredTables = $requiredTables;
        $this->installed = (bool)$installed;
    }

    /**
     * Checks whether the database is ready to work.
     *
     * @return bool
     */
    public function checkDatabase()
    {
        $isChecked = $this->isDatabaseChecked();
        if (null !== $isChecked) {
            return $isChecked;
        }

        $this->dbCheck = SafeDatabaseChecker::tablesExist($this->getConnection(), $this->requiredTables);

        return $this->dbCheck;
    }

    /**
     * Clears the state of this checker.
     * It means that the next call of checkDatabase() will do retest the database state.
     */
    public function clearCheckDatabase()
    {
        $this->dbCheck = null;
        // force the check even if the application is already installed
        // this is required to avoid collisions during the installation
        $this->installed = false;
    }

    /**
     * Indicates whether the database state is already checked.
     *
     * @return bool|null
     */
    protected function isDatabaseChecked()
    {
        if ($this->installed) {
            return true;
        }
        if (null !== $this->dbCheck) {
            return $this->dbCheck;
        }

        return null;
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->doctrine->getConnection();
    }
}
