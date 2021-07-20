<?php

namespace Oro\Bundle\ImapBundle\Provider;

use Http\Client\Common\HttpMethodsClientInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * Provides functionality to work with Google OAuth implementation.
 */
class GoogleOAuthProvider extends AbstractOAuthProvider
{
    private ConfigManager $configManager;
    private SymmetricCrypterInterface $crypter;

    public function __construct(
        HttpMethodsClientInterface $httpClient,
        ResourceOwnerMap $resourceOwnerMap,
        ConfigManager $configManager,
        SymmetricCrypterInterface $crypter
    ) {
        parent::__construct($httpClient, $resourceOwnerMap);
        $this->configManager = $configManager;
        $this->crypter = $crypter;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthorizationUrl(): string
    {
        throw new \LogicException(
            'Not implemented. Use Google API Client Library for JavaScript to perform authorization requests.'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getRedirectUrl(): string
    {
        throw new \LogicException(
            'Not implemented. Use Google API Client Library for JavaScript to perform authorization requests.'
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getAccessTokenUrl(): string
    {
        return 'https://www.googleapis.com/oauth2/v4/token';
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourceOwnerName(): string
    {
        return 'google';
    }

    /**
     * {@inheritDoc}
     */
    protected function getCommonParameters(): array
    {
        return [
            'client_id'     => $this->configManager->get('oro_google_integration.client_id'),
            'client_secret' => $this->crypter->decryptData(
                $this->configManager->get('oro_google_integration.client_secret')
            )
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getAccessTokenParameters(string $code, array $scopes = null): array
    {
        $parameters = parent::getAccessTokenParameters($code, $scopes);
        $parameters['redirect_uri'] = 'postmessage';

        return $parameters;
    }
}
