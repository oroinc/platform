<?php

namespace Oro\Bundle\ImapBundle\Controller;

use Oro\Bundle\ImapBundle\Provider\GoogleOAuthProvider;
use Oro\Bundle\ImapBundle\Provider\OAuthProviderInterface;

/**
 * The controller to receive OAuth access token for Google integration.
 */
class GmailAccessTokenController extends AbstractAccessTokenController
{
    #[\Override]
    protected function getOAuthProvider(): OAuthProviderInterface
    {
        return $this->container->get(GoogleOAuthProvider::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                GoogleOAuthProvider::class,
            ]
        );
    }
}
