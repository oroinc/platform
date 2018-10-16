<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

class PreSetRequireUpdateEvent extends PreFlushConfigEvent
{
    /** @var bool */
    private $updateRequired = false;

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
    public function isUpdateRequired()
    {
        return $this->updateRequired;
    }
}
