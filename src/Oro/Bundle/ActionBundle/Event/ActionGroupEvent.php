<?php

namespace Oro\Bundle\ActionBundle\Event;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;

/**
 * Action Bundle event containing ActionData and ActionGroupDefinition.
 */
abstract class ActionGroupEvent extends ActionDataAwareEvent
{
    public function __construct(
        ActionData $actionData,
        private ActionGroupDefinition $actionGroupDefinition,
        ?Collection $errors = null
    ) {
        parent::__construct($actionData, $errors);
    }

    public function getActionGroupDefinition(): ActionGroupDefinition
    {
        return $this->actionGroupDefinition;
    }
}
