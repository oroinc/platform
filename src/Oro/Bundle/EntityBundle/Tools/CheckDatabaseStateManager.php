<?php

namespace Oro\Bundle\EntityBundle\Tools;

/**
 * Manages the state of multiple database checkers.
 *
 * This manager coordinates a collection of database checkers and provides a way
 * to clear their cached state, forcing them to re-check the database state on
 * the next call.
 */
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
