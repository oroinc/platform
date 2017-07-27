<?php

namespace Oro\Bundle\EntityBundle\Tools;

class CheckDatabaseStateManager
{
    /** @var DatabaseChecker[] */
    private $databaseCheckers;

    /**
     * @param DatabaseChecker[] $databaseCheckers
     */
    public function __construct(array $databaseCheckers)
    {
        $this->databaseCheckers = $databaseCheckers;
    }

    /**
     * Clears the state of all database checkers to make sure that
     * they will re-check the database state at the next call of "checkDatabase" method.
     */
    public function clearState()
    {
        foreach ($this->databaseCheckers as $databaseChecker) {
            $databaseChecker->clearCheckDatabase();
        }
    }
}
