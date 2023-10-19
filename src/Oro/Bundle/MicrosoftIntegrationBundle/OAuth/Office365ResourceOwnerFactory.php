<?php

namespace Oro\Bundle\MicrosoftIntegrationBundle\OAuth;

use HWI\Bundle\OAuthBundle\OAuth\RequestDataStorageInterface;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\Office365ResourceOwner;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The OAuth single sign-on authentication resource owner factory for Microsoft Office 365.
 */
class Office365ResourceOwnerFactory
{
    public function create(
        SymmetricCrypterInterface $crypter,
        ConfigManager $configManager,
        HttpClientInterface $httpClient,
        HttpUtils $httpUtils,
        RequestDataStorageInterface $storage,
        string $name,
        array $config
    ): Office365ResourceOwner {
        $clientId = $configManager->get('oro_microsoft_integration.client_id');
        if ($clientId) {
            $config['client_id'] = $clientId;
        }

        $clientSecretEncrypted = $configManager->get('oro_microsoft_integration.client_secret');
        if ($clientSecretEncrypted) {
            $clientSecretDecrypted = $crypter->decryptData($clientSecretEncrypted);
            $config['client_secret'] = $clientSecretDecrypted;
        }

        return new Office365ResourceOwner(
            $httpClient,
            $httpUtils,
            $config,
            $name,
            $storage
        );
    }
}
