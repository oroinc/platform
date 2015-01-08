<?php

namespace Oro\Bundle\PlatformBundle\EventListener;

interface OptionalListenerInterface
{
    /**
     * Set if current listener is enabled
     *
     * @param bool $enabled
     */
    public function setEnabled($enabled = true);
}
