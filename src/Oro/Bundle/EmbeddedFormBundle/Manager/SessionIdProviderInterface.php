<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

interface SessionIdProviderInterface
{
    /**
     * Gets the embedded form session id.
     *
     * @return string|null
     */
    public function getSessionId();
}
