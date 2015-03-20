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
        $clientIdKey = sprintf('oro_sso.%s_sso_client_id', $this->getName());
        if ($clientId = $configManager->get($clientIdKey)) {
            $this->options['client_id'] = $clientId;
        }

        $clientSecretKey = sprintf('oro_sso.%s_sso_client_secret', $this->getName());
        if ($clientSecret = $configManager->get($clientSecretKey)) {
            $this->options['client_secret'] = $clientSecret;
        }
    }
}
