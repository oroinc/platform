<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;

/**
 * Decorates method isDatabaseChecked of DatabaseChecker and returns true if the object is locked.
 */
class ConfigDatabaseChecker extends DatabaseChecker
{
    private LockObject $lockObject;

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
     * {@inheritDoc}
     */
    protected function isDatabaseChecked(): ?bool
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
