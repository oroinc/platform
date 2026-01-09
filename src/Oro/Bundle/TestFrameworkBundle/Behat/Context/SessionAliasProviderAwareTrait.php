<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

/**
 * Provides functionality for classes to store and access a session alias provider.
 *
 * This trait implements the {@see SessionAliasProviderAwareInterface}, allowing classes to
 * maintain a reference to a {@see SessionAliasProvider} for managing multiple named browser sessions.
 */
trait SessionAliasProviderAwareTrait
{
    /**
     * @var SessionAliasProvider
     */
    protected $sessionAliasProvider;

    /**
     * @param SessionAliasProvider $provider
     * @return void
     */
    public function setSessionAliasProvider(SessionAliasProvider $provider)
    {
        $this->sessionAliasProvider = $provider;
    }
}
