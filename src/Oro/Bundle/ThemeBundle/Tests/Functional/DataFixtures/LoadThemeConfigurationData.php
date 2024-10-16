<?php

namespace Oro\Bundle\ThemeBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ThemeBundle\Entity\Enum\ThemeConfigurationType;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

class LoadThemeConfigurationData extends AbstractFixture
{
    use UserUtilityTrait;

    public const THEME_CONFIGURATION_1 = 'Theme 1';

    protected array $themeConfigurationsData = [
        [
            'name' => self::THEME_CONFIGURATION_1,
            'type' => ThemeConfigurationType::Storefront,
            'theme' => 'default',
        ],
    ];

    #[\Override]
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        foreach ($this->themeConfigurationsData as $themeConfigurationData) {
            $themeConfiguration = (new ThemeConfiguration())
                ->setName($themeConfigurationData['name'])
                ->setType($themeConfigurationData['type'] ?? ThemeConfigurationType::Storefront)
                ->setConfiguration($this->processConfiguration($themeConfigurationData['configuration'] ?? []))
                ->setTheme($themeConfigurationData['theme'])
                ->setOwner($businessUnit)
                ->setOrganization($organization);

            $this->setReference($themeConfiguration->getName(), $themeConfiguration);

            $manager->persist($themeConfiguration);
        }

        $manager->flush();
    }

    protected function processConfiguration(array $configuration): array
    {
        return $configuration;
    }
}
