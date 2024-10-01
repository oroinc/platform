<?php

namespace Oro\Bundle\ImapBundle\Provider;

use HWI\Bundle\OAuthBundle\Security\Http\ResourceOwnerMapInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Provides functionality to work with Microsoft OAuth implementation.
 */
class MicrosoftOAuthProvider extends AbstractOAuthProvider
{
    private ConfigManager $configManager;
    private SymmetricCrypterInterface $crypter;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        HttpClientInterface $httpClient,
        ResourceOwnerMapInterface $resourceOwnerMap,
        ConfigManager $configManager,
        SymmetricCrypterInterface $crypter,
        UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($httpClient, $resourceOwnerMap);
        $this->configManager = $configManager;
        $this->crypter = $crypter;
        $this->urlGenerator = $urlGenerator;
    }

    #[\Override]
    public function getAuthorizationUrl(): string
    {
        return str_replace(
            '{tenant}',
            $this->configManager->get('oro_microsoft_integration.tenant'),
            'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/authorize'
        );
    }

    #[\Override]
    public function getRedirectUrl(): string
    {
        return $this->urlGenerator->generate(
            'oro_imap_microsoft_access_token',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    #[\Override]
    protected function getAccessTokenUrl(): string
    {
        return str_replace(
            '{tenant}',
            $this->configManager->get('oro_microsoft_integration.tenant'),
            'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token'
        );
    }

    #[\Override]
    protected function getResourceOwnerName(): string
    {
        return 'office365';
    }

    #[\Override]
    protected function getCommonParameters(): array
    {
        return [
            'client_id'     => $this->configManager->get('oro_microsoft_integration.client_id'),
            'client_secret' => $this->crypter->decryptData(
                $this->configManager->get('oro_microsoft_integration.client_secret')
            )
        ];
    }

    #[\Override]
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
