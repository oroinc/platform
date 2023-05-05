<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Migrations\Data\Demo\ORM\LoadLocalizationDemoData;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Utility methods for working with localizations in demo data fixtures.
 *
 * Make sure to add \Oro\Bundle\LocaleBundle\Migrations\Data\Demo\ORM\LoadLocalizationDemoData::class
 * to getDependencies() in exhibiting fixture classes:
 *      \Oro\Bundle\LocaleBundle\Migrations\Data\Demo\ORM\LoadLocalizationDemoData::class
 */
trait LocalizedDemoDataFixtureTrait
{
    use ContainerAwareTrait;

    private ?string $defaultLocale = null;
    private ?Localization $defaultLocalization = null;
    private ?Localization $englishLocalization = null;
    private ?Localization $frenchLocalization = null;
    private ?Localization $germanLocalization = null;

    public function getDependencies(): array
    {
        return [
            LoadLocalizationDemoData::class
        ];
    }

    protected function getDefaultLocale(ObjectManager $manager): string
    {
        return $this->defaultLocale
            ?? ($this->defaultLocale = $this->getDefaultLocalization($manager)->getFormattingCode());
    }

    protected function getDefaultLocalization(ObjectManager $manager): Localization
    {
        if (null === $this->defaultLocalization) {
            /* @var $configManager ConfigManager */
            $configManager = $this->container->get('oro_config.global');
            $defaultLocalizationId = $configManager->get(
                Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
            );
            $localizationRepository = $manager->getRepository(Localization::class);
            $this->defaultLocalization = $localizationRepository->find($defaultLocalizationId);
        }
        return $this->defaultLocalization;
    }

    protected function getEnglishLocalization(ObjectManager $manager): Localization
    {
        return $this->englishLocalization
            ?? (
                $this->englishLocalization =
                    $manager->getRepository(Localization::class)
                        ->findOneByLanguageCodeAndFormattingCode('en_US', 'en_US')
                    ?? $manager->getRepository(Localization::class)
                        ->findOneByLanguageCodeAndFormattingCode('en', 'en_US')
            );
    }

    protected function getFrenchLocalization(ObjectManager $manager): Localization
    {
        return $this->frenchLocalization
            ?? ($this->frenchLocalization = $manager->getRepository(Localization::class)
                    ->findOneByLanguageCodeAndFormattingCode('fr_FR', 'fr_FR'));
    }

    protected function getGermanLocalization(ObjectManager $manager): Localization
    {
        return $this->germanLocalization
            ?? ($this->germanLocalization = $manager->getRepository(Localization::class)
                    ->findOneByLanguageCodeAndFormattingCode('de_DE', 'de_DE'));
    }

    protected function isDefaultEnglish(ObjectManager $manager): bool
    {
        return 'en_US' === $this->getDefaultLocale($manager);
    }

    protected function isDefaultFrench(ObjectManager $manager): bool
    {
        return 'fr_FR' === $this->getDefaultLocale($manager);
    }

    protected function isDefaultGerman(ObjectManager $manager): bool
    {
        return 'de_DE' === $this->getDefaultLocale($manager);
    }
}
