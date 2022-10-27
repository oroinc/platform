<?php

namespace Oro\Bundle\GoogleIntegrationBundle\OAuth;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\GoogleResourceOwner as BaseGoogleResourceOwner;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * The OAuth single sign-on authentication resource owner for Google.
 */
class GoogleResourceOwner extends BaseGoogleResourceOwner
{
    /** @var SymmetricCrypterInterface */
    private $crypter;

    /**
     * Sets crypter instance
     */
    public function setCrypter(SymmetricCrypterInterface $crypter): void
    {
        $this->crypter = $crypter;
    }

    public function configureCredentials(ConfigManager $configManager): void
    {
        $clientId = $configManager->get('oro_google_integration.client_id');
        if ($clientId) {
            $this->options['client_id'] = $clientId;
        }

        $clientSecretEncrypted = $configManager->get('oro_google_integration.client_secret');
        if ($clientSecretEncrypted) {
            $clientSecretDecrypted = $this->crypter->decryptData($clientSecretEncrypted);
            $this->options['client_secret'] = $clientSecretDecrypted;
        }
    }
}
