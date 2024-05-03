<?php

namespace Oro\Bundle\ThemeBundle\Migrations\Data;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration as LayoutThemeConfiguration;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Layout\Extension\Theme\Model\ThemeDefinitionBagInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Abstraction for loading Theme Configurations Data
 */
abstract class AbstractLoadThemeConfiguration extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected ?ObjectManager $manager;

    protected ?ThemeDefinitionBagInterface $themeDefinitionBag;

    protected ?ConfigManager $configManager;

    abstract protected function getConfigManager(): ConfigManager;

    abstract protected function getScopes(): iterable;

    abstract protected function isApplicable(): bool;

    /**
     * Options from System configuration that should replace values from theme.yml
     */
    abstract protected function getThemeConfigurationKeys(): array;

    public function load(ObjectManager $manager): void
    {
        $this->init($manager);

        if (!$this->isApplicable()) {
            return;
        }

        foreach ($this->getScopes() as $scope) {
            $frontendTheme = $this->getFrontendTheme($this->configManager, $scope);
            if (!$frontendTheme) {
                continue;
            }

            $definition = $this->themeDefinitionBag->getThemeDefinition($frontendTheme);
            $organization = $this->getOrganization($scope);

            $themeConfiguration = (new ThemeConfiguration())
                ->setTheme($frontendTheme)
                ->setName($this->getThemeConfigurationName($definition, $scope))
                ->setOrganization($organization)
                ->setOwner($this->getOwner($scope, $organization))
                ->setConfiguration($this->buildConfigurationFromDefinition($definition, $scope));

            $this->manager->persist($themeConfiguration);
            $manager->flush();

            $this->configManager->set(
                Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
                $themeConfiguration->getId(),
                $scope
            );
        }

        $this->configManager->flush();
    }

    protected function init(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->themeDefinitionBag = $this->container->get('oro_layout.theme_extension.configuration.provider');
        $this->configManager = $this->getConfigManager();
    }

    protected function getFrontendTheme(ConfigManager $configManager, ?object $scope): ?string
    {
        return $configManager->get('oro_frontend.frontend_theme', false, false, $scope);
    }

    protected function getThemeConfigurationName(array $definition, object|null $scope): string
    {
        return is_object($scope)
            ? sprintf(
                '%s [%s: %s]',
                $definition['label'],
                (new \ReflectionClass($scope))->getShortName(),
                $scope->getName()
            )
            : $definition['label'];
    }

    protected function getOrganization(object|null $scope): Organization
    {
        return match (true) {
            $scope instanceof Organization => $scope,
            $scope instanceof Website => $scope->getOrganization(),
            default => $this->manager->getRepository(Organization::class)->getFirst()
        };
    }

    protected function getOwner(object|null $scope, Organization $organization): ?BusinessUnit
    {
        return match (true) {
            $scope instanceof Website => $scope->getOwner(),
            default => $this->getOwnerByOrganization($organization)
        };
    }

    protected function getOwnerByOrganization(Organization $organization): ?BusinessUnit
    {
        $businessUnits = $organization->getBusinessUnits();

        return $businessUnits->count() ? $businessUnits->first() : null;
    }

    protected function buildConfigurationFromDefinition(
        array $definition,
        object|null $scope
    ): array {
        $configuration = [];
        $definitionConfiguration = $definition['configuration'] ?? [];
        foreach ($definitionConfiguration['sections'] ?? [] as $sKey => $section) {
            foreach ($section['options'] ?? [] as $oKey => $option) {
                $configurationKey = LayoutThemeConfiguration::buildOptionKey($sKey, $oKey);
                $configurationValue = $option['default'] ?? null;
                if ($option['type'] === 'checkbox') {
                    $configurationValue = $configurationValue === 'checked';
                }

                $themeConfigurationKeys = $this->getThemeConfigurationKeys();
                if (isset($themeConfigurationKeys[$configurationKey])) {
                    $configValue = $this->configManager->get(
                        $themeConfigurationKeys[$configurationKey],
                        false,
                        false,
                        $scope
                    );

                    if ($configValue) {
                        $configurationValue = is_array($configValue) ? current($configValue) : $configValue;
                    }
                }

                $configuration[$configurationKey] = $configurationValue;
            }
        }

        return $configuration;
    }
}
