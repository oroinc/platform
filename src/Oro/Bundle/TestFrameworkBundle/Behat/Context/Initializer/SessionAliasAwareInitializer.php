<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\SessionAliasProvider;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\SessionAliasProviderAwareInterface;

/**
 * Initializes Behat contexts with the session alias provider.
 *
 * This initializer injects the {@see SessionAliasProvider} into any Behat context that implements
 * {@see SessionAliasProviderAwareInterface}, enabling contexts to manage multiple browser sessions
 * with named aliases during test execution.
 */
class SessionAliasAwareInitializer implements ContextInitializer
{
    /**
     * @var SessionAliasProvider
     */
    private $provider;

    public function __construct(SessionAliasProvider $provider)
    {
        $this->provider = $provider;
    }

    #[\Override]
    public function initializeContext(Context $context)
    {
        if ($context instanceof SessionAliasProviderAwareInterface) {
            $context->setSessionAliasProvider($this->provider);
        }
    }
}
