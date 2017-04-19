<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

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
