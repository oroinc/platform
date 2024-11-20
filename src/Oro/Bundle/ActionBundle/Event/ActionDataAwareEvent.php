<?php

namespace Oro\Bundle\ActionBundle\Event;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Action Bundle event containing ActionData entity.
 */
abstract class ActionDataAwareEvent extends Event
{
    public function __construct(
        private ActionData $actionData,
        private ?Collection $errors = null
    ) {
    }

    abstract public function getName(): string;

    public function getActionData(): ActionData
    {
        return $this->actionData;
    }

    public function setActionData(ActionData $actionData): void
    {
        $this->actionData = $actionData;
    }

    public function getErrors(): ?Collection
    {
        return $this->errors;
    }
}
