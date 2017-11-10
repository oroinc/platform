<?php

namespace Oro\Bundle\PlatformBundle\EventListener;

trait OptionalListenerTrait
{
    /** @var bool */
    protected $enabled = true;

    /**
     * Set if current listener is enabled
     *
     * @param bool $enabled
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }
}
