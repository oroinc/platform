<?php

namespace Oro\Bundle\ImapBundle\Controller;

use Oro\Bundle\ImapBundle\Provider\GoogleOAuthProvider;
use Oro\Bundle\ImapBundle\Provider\OAuthProviderInterface;

/**
 * The controller to receive OAuth access token for Google integration.
 */
class GmailAccessTokenController extends AbstractAccessTokenController
{
    /**
     * {@inheritDoc}
     */
    protected function getOAuthProvider(): OAuthProviderInterface
    {
        return $this->get(GoogleOAuthProvider::class);
    }

    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                GoogleOAuthProvider::class,
            ]
        );
    }
}
