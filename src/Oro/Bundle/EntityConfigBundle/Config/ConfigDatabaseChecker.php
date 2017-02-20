<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;

class ConfigDatabaseChecker extends DatabaseChecker
{
    /** @var LockObject */
    private $lockObject;

    /**
     * @param LockObject      $lockObject     The entity config lock object
     * @param ManagerRegistry $doctrine       The instance of Doctrine ManagerRegistry object
     * @param string[]        $requiredTables The list of tables that should exist in the database
     * @param mixed           $installed      The flag indicates that the application is already installed
     */
    public function __construct(LockObject $lockObject, ManagerRegistry $doctrine, array $requiredTables, $installed)
    {
        parent::__construct($doctrine, $requiredTables, $installed);
        $this->lockObject = $lockObject;
    }

    /**
     * {@inheritdoc}
     */
    protected function isDatabaseChecked()
    {
        $isChecked = parent::isDatabaseChecked();
        if (null !== $isChecked) {
            return $isChecked;
        }
        if ($this->lockObject->isLocked()) {
            return true;
        }

        return null;
    }
}
