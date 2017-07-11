<?php

namespace Oro\Bundle\LocaleBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class LocalizationContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * @Given I enable the existing localizations
     */
    public function loadFixtures()
    {
        /* @var $configManager ConfigManager */
        $configManager = $this->getContainer()->get('oro_config.global');

        /* @var $localizations Localization[] */
        $localizations = $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepository(Localization::class)
            ->findAll();

        $configManager->set(
            'oro_locale.enabled_localizations',
            array_map(function (Localization $item) {
                return $item->getId();
            }, $localizations)
        );

        $configManager->flush();
    }
}
