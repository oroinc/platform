<?php

namespace Oro\Bundle\TranslationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface;

class LoadLanguageData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /* @var $configManager ConfigManager */
        $configManager = $this->container->get('oro_config.global');

        $defaultLanguage = $configManager->get(Configuration::getConfigKeyByName(Configuration::LANGUAGE));
        $enabledLanguages = $configManager->get(Configuration::getConfigKeyByName(Configuration::LANGUAGES));

        $availableLanguages = array_merge(
            [$defaultLanguage],
            array_keys(
                array_filter(
                    $configManager->get(TranslationStatusInterface::CONFIG_KEY),
                    function ($status) {
                        return $status === TranslationStatusInterface::STATUS_ENABLED;
                    }
                )
            )
        );

        foreach ($availableLanguages as $code) {
            $language = new Language();
            $language
                ->setCode($code)
                ->setEnabled(in_array($code, $enabledLanguages, true));

            $manager->persist($language);
        }

        $manager->flush();
    }
}
