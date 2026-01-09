<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

/**
 * Defines the contract for providing the session ID associated with an embedded form.
 *
 * Implementations of this interface are responsible for retrieving and managing the session
 * identifier used to track and maintain state for embedded forms across requests. This is
 * essential for maintaining form context and user session information in embedded form scenarios.
 */
interface SessionIdProviderInterface
{
    /**
     * Gets the embedded form session id.
     *
     * @return string|null
     */
    public function getSessionId();
}
