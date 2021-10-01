<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;

/**
 * Decorate method isDatabaseChecked of DatabaseChecker and return true if the object is locked
 */
class ConfigDatabaseChecker extends DatabaseChecker
{
    /** @var LockObject */
    private LockObject $lockObject;

    /**
     * @param LockObject $lockObject The entity config lock object
     * @param ManagerRegistry $doctrine The instance of Doctrine ManagerRegistry object
     * @param string[] $requiredTables The list of tables that should exist in the database
     * @param ApplicationState $applicationState The flag indicates that the application is already installed
     */
    public function __construct(
        LockObject $lockObject,
        ManagerRegistry $doctrine,
        array $requiredTables,
        ApplicationState $applicationState
    ) {
        parent::__construct($doctrine, $requiredTables, $applicationState);
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
