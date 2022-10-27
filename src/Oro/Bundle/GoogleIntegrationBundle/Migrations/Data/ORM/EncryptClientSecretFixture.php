<?php

namespace Oro\Bundle\GoogleIntegrationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Migration for encrypting Google API client secret value for storing in database
 */
class EncryptClientSecretFixture extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');
        if ($secret = $configManager->get('oro_google_integration.client_secret')) {
            /** @var SymmetricCrypterInterface $crypter */
            $crypter = $this->container->get('oro_security.encoder.default');
            $secret = $crypter->encryptData($secret);
            $configManager->set('oro_google_integration.client_secret', $secret);

            $configManager->flush();
        }
    }
}
