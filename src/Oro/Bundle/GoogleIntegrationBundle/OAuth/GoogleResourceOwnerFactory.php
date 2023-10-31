<?php

namespace Oro\Bundle\GoogleIntegrationBundle\OAuth;

use HWI\Bundle\OAuthBundle\OAuth\RequestDataStorageInterface;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\GoogleResourceOwner;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The OAuth single sign-on authentication resource owner factory for Google.
 */
class GoogleResourceOwnerFactory
{
    public function create(
        SymmetricCrypterInterface $crypter,
        ConfigManager $configManager,
        HttpClientInterface $httpClient,
        HttpUtils $httpUtils,
        RequestDataStorageInterface $storage,
        string $name,
        array $config
    ): GoogleResourceOwner {
        $clientId = $configManager->get('oro_google_integration.client_id');
        if ($clientId) {
            $config['client_id'] = $clientId;
        }

        $clientSecretEncrypted = $configManager->get('oro_google_integration.client_secret');
        if ($clientSecretEncrypted) {
            $clientSecretDecrypted = $crypter->decryptData($clientSecretEncrypted);
            $config['client_secret'] = $clientSecretDecrypted;
        }

        return new GoogleResourceOwner(
            $httpClient,
            $httpUtils,
            $config,
            $name,
            $storage
        );
    }
}
