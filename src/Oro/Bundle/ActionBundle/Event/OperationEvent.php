<?php

namespace Oro\Bundle\ActionBundle\Event;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;

/**
 * Action Bundle event containing ActionData and OperationDefinition.
 */
abstract class OperationEvent extends ActionDataAwareEvent
{
    public function __construct(
        ActionData $actionData,
        private OperationDefinition $operationDefinition,
        ?Collection $errors = null
    ) {
        parent::__construct($actionData, $errors);
    }

    public function getOperationDefinition(): OperationDefinition
    {
        return $this->operationDefinition;
    }
}
