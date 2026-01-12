<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

/**
 * Defines the contract for classes that need to be aware of the session alias provider.
 *
 * Classes implementing this interface can be injected with a {@see SessionAliasProvider}
 * to manage and access multiple named browser sessions.
 */
interface SessionAliasProviderAwareInterface
{
    /**
     * @param SessionAliasProvider $provider
     * @return void
     */
    public function setSessionAliasProvider(SessionAliasProvider $provider);
}
