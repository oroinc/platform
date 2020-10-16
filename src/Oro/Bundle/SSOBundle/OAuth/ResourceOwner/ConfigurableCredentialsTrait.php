<?php

namespace Oro\Bundle\SSOBundle\OAuth\ResourceOwner;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * Common trait for Google application config aware services
 */
trait ConfigurableCredentialsTrait
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
        $clientIdKey = 'oro_google_integration.client_id';
        if ($clientId = $configManager->get($clientIdKey)) {
            $this->options['client_id'] = $clientId;
        }

        $clientSecretKey = 'oro_google_integration.client_secret';
        if ($clientSecretEncrypted = $configManager->get($clientSecretKey)) {
            $clientSecretDecrypted = $this->crypter->decryptData($clientSecretEncrypted);
            $this->options['client_secret'] = $clientSecretDecrypted;
        }
    }
}
