<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;

/**
 * Dummy implementation of the operation service.
 */
abstract class AbstractOperationService implements OperationServiceInterface
{
    public function isPreConditionAllowed(ActionData $data, ?Collection $errors = null): bool
    {
        return true;
    }

    public function isConditionAllowed(ActionData $data, ?Collection $errors = null): bool
    {
        return true;
    }

    public function execute(ActionData $data): void
    {
    }
}
