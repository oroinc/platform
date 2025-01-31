<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;

/**
 * Interface for Operation services.
 */
interface OperationServiceInterface
{
    public function isPreConditionAllowed(ActionData $data, ?Collection $errors = null): bool;

    public function isConditionAllowed(ActionData $data, ?Collection $errors = null): bool;

    public function execute(ActionData $data): void;
}
