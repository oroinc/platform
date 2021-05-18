<?php

namespace Oro\Bundle\PlatformBundle\EventListener;

/**
 * Default implementation of OptionalListenerInterface
 */
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
        $this->enabled = (bool)$enabled;
    }
}
