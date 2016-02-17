<?php

namespace Oro\Bundle\SSOBundle\OAuth\ResourceOwner;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

trait ConfigurableCredentialsTrait
{
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
        if ($clientSecret = $configManager->get($clientSecretKey)) {
            $this->options['client_secret'] = $clientSecret;
        }
    }
}
