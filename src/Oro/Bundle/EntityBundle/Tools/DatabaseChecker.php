<?php

namespace Oro\Bundle\EntityBundle\Tools;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;

/**
 * Checks if required tables exists in the database.
 */
class DatabaseChecker
{
    private ManagerRegistry $doctrine;
    /** @var string[] */
    private array $requiredTables;
    private ApplicationState $applicationState;
    private ?bool $installed = null;
    private ?bool $dbCheck = null;

    public function __construct(
        ManagerRegistry $doctrine,
        array $requiredTables,
        ApplicationState $applicationState
    ) {
        $this->doctrine = $doctrine;
        $this->requiredTables = $requiredTables;
        $this->applicationState = $applicationState;
    }

    /**
     * Checks whether the database is ready to work.
     */
    public function checkDatabase(): bool
    {
        $isChecked = $this->isDatabaseChecked();
        if (null !== $isChecked) {
            return $isChecked;
        }

        $this->dbCheck = SafeDatabaseChecker::tablesExist($this->doctrine->getConnection(), $this->requiredTables);

        return $this->dbCheck;
    }

    /**
     * Clears the state of this checker.
     * It means that the next call of checkDatabase() will do retest the database state.
     */
    public function clearCheckDatabase(): void
    {
        $this->dbCheck = null;
        // force the check even if the application is already installed
        // this is required to avoid collisions during the installation
        $this->installed = false;
    }

    /**
     * Indicates whether the database state is already checked.
     */
    protected function isDatabaseChecked(): ?bool
    {
        if ($this->isInstalled()) {
            return true;
        }

        return $this->dbCheck ?? null;
    }

    private function isInstalled(): bool
    {
        if (null === $this->installed) {
            $this->installed = $this->applicationState->isInstalled();
        }

        return $this->installed;
    }
}
