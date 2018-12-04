<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

/**
 * This event is used to decide if entity config update is required.
 * Occurs before setting config state to Require update.
 */
class PreSetRequireUpdateEvent extends PreFlushConfigEvent
{
    /** @var bool */
    private $updateRequired = true;

    /**
     * @param bool $updateRequired
     * @return $this
     */
    public function setUpdateRequired(bool $updateRequired)
    {
        $this->updateRequired = $updateRequired;

        return $this;
    }

    /**
     * @return bool
     */
    public function isUpdateRequired(): bool
    {
        return $this->updateRequired;
    }
}
