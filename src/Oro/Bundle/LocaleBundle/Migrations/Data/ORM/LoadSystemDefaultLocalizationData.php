<?php

namespace Oro\Bundle\LocaleBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;

class LoadSystemDefaultLocalizationData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Localization $localization */
        $localization = $this->getReference('default_localization');
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');
        $configManager->set('oro_locale.' . Configuration::DEFAULT_LOCALIZATION, $localization->getId());
        $configManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadLocalizationData::class];
    }
}
