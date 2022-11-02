<?php

namespace Oro\Bundle\ImapBundle\Provider;

use Http\Client\Common\HttpMethodsClientInterface;
use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMap;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Provides functionality to work with Microsoft OAuth implementation.
 */
class MicrosoftOAuthProvider extends AbstractOAuthProvider
{
    private ConfigManager $configManager;
    private SymmetricCrypterInterface $crypter;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        HttpMethodsClientInterface $httpClient,
        ResourceOwnerMap $resourceOwnerMap,
        ConfigManager $configManager,
        SymmetricCrypterInterface $crypter,
        UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($httpClient, $resourceOwnerMap);
        $this->configManager = $configManager;
        $this->crypter = $crypter;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthorizationUrl(): string
    {
        return str_replace(
            '{tenant}',
            $this->configManager->get('oro_microsoft_integration.tenant'),
            'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/authorize'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getRedirectUrl(): string
    {
        return $this->urlGenerator->generate(
            'oro_imap_microsoft_access_token',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getAccessTokenUrl(): string
    {
        return str_replace(
            '{tenant}',
            $this->configManager->get('oro_microsoft_integration.tenant'),
            'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token'
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getResourceOwnerName(): string
    {
        return 'office365';
    }

    /**
     * {@inheritDoc}
     */
    protected function getCommonParameters(): array
    {
        return [
            'client_id'     => $this->configManager->get('oro_microsoft_integration.client_id'),
            'client_secret' => $this->crypter->decryptData(
                $this->configManager->get('oro_microsoft_integration.client_secret')
            )
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getAccessTokenParameters(string $code, array $scopes = null): array
    {
        $parameters = parent::getAccessTokenParameters($code, $scopes);
        $parameters['redirect_uri'] = $this->getRedirectUrl();
        if (null !== $scopes && !empty($scopes)) {
            $parameters['scope'] = implode(' ', $scopes);
        }

        return $parameters;
    }
}
