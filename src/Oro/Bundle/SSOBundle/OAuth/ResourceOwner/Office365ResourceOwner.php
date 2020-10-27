<?php

namespace Oro\Bundle\SSOBundle\OAuth\ResourceOwner;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\Office365ResourceOwner as BaseResourceOwner;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * OAuth resource owner for Microsoft Office 365
 */
class Office365ResourceOwner extends BaseResourceOwner
{
    /** @var SymmetricCrypterInterface */
    protected $crypter;

    /**
     * Sets crypter instance
     *
     * @param SymmetricCrypterInterface $crypter
     */
    public function setCrypter(SymmetricCrypterInterface $crypter)
    {
        $this->crypter = $crypter;
    }

    /**
     * Configure credentials
     *
     * @param ConfigManager $configManager
     */
    public function configureCredentials(ConfigManager $configManager)
    {
        if ($clientId = $configManager->get('oro_microsoft_integration.client_id')) {
            $this->options['client_id'] = $clientId;
        }

        if ($clientSecretEncrypted = $configManager->get('oro_microsoft_integration.client_secret')) {
            $clientSecretDecrypted = $this->crypter->decryptData($clientSecretEncrypted);
            $this->options['client_secret'] = $clientSecretDecrypted;
        }
    }
}
