<?php

namespace Oro\Bundle\ThemeBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\UserBundle\Entity\User;

class LoadThemeConfigurationData extends AbstractFixture implements DependentFixtureInterface
{
    public const string THEME_CONFIGURATION_1 = 'Theme 1';

    protected array $themeConfigurationsData = [
        [
            'name' => self::THEME_CONFIGURATION_1,
            'type' => 'storefront',
            'theme' => 'default',
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadUser::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        foreach ($this->themeConfigurationsData as $themeConfigurationData) {
            $themeConfiguration = (new ThemeConfiguration())
                ->setName($themeConfigurationData['name'])
                ->setType($themeConfigurationData['type'])
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
