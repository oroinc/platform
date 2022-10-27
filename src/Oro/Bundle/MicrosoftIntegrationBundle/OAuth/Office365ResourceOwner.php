<?php

namespace Oro\Bundle\MicrosoftIntegrationBundle\OAuth;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\Office365ResourceOwner as BaseResourceOwner;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * The OAuth single sign-on authentication resource owner for Microsoft Office 365.
 */
class Office365ResourceOwner extends BaseResourceOwner
{
    /** @var SymmetricCrypterInterface */
    private $crypter;

    public function setCrypter(SymmetricCrypterInterface $crypter): void
    {
        $this->crypter = $crypter;
    }

    public function configureCredentials(ConfigManager $configManager): void
    {
        $clientId = $configManager->get('oro_microsoft_integration.client_id');
        if ($clientId) {
            $this->options['client_id'] = $clientId;
        }

        $clientSecretEncrypted = $configManager->get('oro_microsoft_integration.client_secret');
        if ($clientSecretEncrypted) {
            $clientSecretDecrypted = $this->crypter->decryptData($clientSecretEncrypted);
            $this->options['client_secret'] = $clientSecretDecrypted;
        }
    }
}
