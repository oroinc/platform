<?php

namespace Oro\Bundle\UIBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UIBundle\DependencyInjection\Configuration as UIConfiguration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class TrimApplicationUrl extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Remove / from the end of the application url
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');

        $applicationUrlConfigKey = $this->getApplicationUrlConfigKey();

        $appUrl = $configManager->get($applicationUrlConfigKey);
        $appUrl = rtrim($appUrl, '/');

        $configManager->set($applicationUrlConfigKey, $appUrl);
        $configManager->flush();
    }

    /**
     * @return string
     */
    protected function getApplicationUrlConfigKey()
    {
        return UIConfiguration::getFullConfigKey(UIConfiguration::APPLICATION_URL_KEY);
    }
}
