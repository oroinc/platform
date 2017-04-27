<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

interface SessionAliasProviderAwareInterface
{
    /**
     * @param SessionAliasProvider $provider
     * @return void
     */
    public function setSessionAliasProvider(SessionAliasProvider $provider);
}
