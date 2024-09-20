<?php

declare(strict_types=1);

namespace Oro\Bundle\ThemeBundle\Provider;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides theme configuration options and theme name for a theme that is set in the system configuration.
 */
class ThemeConfigurationProvider implements ResetInterface
{
    private array $configurationCache = [];
    private array $nameCache = [];

    public function __construct(
        private ConfigManager $configManager,
        private ManagerRegistry $doctrine
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->configurationCache = [];
        $this->nameCache = [];
    }

    public function getThemeConfigurationOptions(object|int|null $scopeIdentifier = null): array
    {
        $themeConfigId = $this->getThemeConfigId($scopeIdentifier);
        if (!$themeConfigId) {
            return [];
        }

        if (!\array_key_exists($themeConfigId, $this->configurationCache)) {
            $this->configurationCache[$themeConfigId] = $this->loadValue($themeConfigId, 'configuration') ?? [];
        }

        return $this->configurationCache[$themeConfigId];
    }

    public function hasThemeConfigurationOption(
        string $configurationKey,
        object|int|null $scopeIdentifier = null
    ): bool {
        return \array_key_exists($configurationKey, $this->getThemeConfigurationOptions($scopeIdentifier));
    }

    public function getThemeConfigurationOption(
        string $configurationKey,
        object|int|null $scopeIdentifier = null
    ): mixed {
        return $this->getThemeConfigurationOptions($scopeIdentifier)[$configurationKey] ?? null;
    }

    public function getThemeName(object|int|null $scopeIdentifier = null): ?string
    {
        $themeConfigId = $this->getThemeConfigId($scopeIdentifier);
        if (!$themeConfigId) {
            return null;
        }

        if (!\array_key_exists($themeConfigId, $this->nameCache)) {
            $this->nameCache[$themeConfigId] = $this->loadValue($themeConfigId, 'theme');
        }

        return $this->nameCache[$themeConfigId];
    }

    private function getThemeConfigId(object|int|null $scopeIdentifier = null): ?int
    {
        return $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION),
            false,
            false,
            $scopeIdentifier
        );
    }

    private function loadValue(int $themeConfigId, string $fieldName): mixed
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(ThemeConfiguration::class);
        QueryBuilderUtil::checkField($fieldName);
        $rows = $em->createQueryBuilder()
            ->from(ThemeConfiguration::class, 'e')
            ->select(QueryBuilderUtil::sprintf('e.%s', $fieldName))
            ->where('e.id = :id')
            ->setParameter('id', $themeConfigId, Types::INTEGER)
            ->getQuery()
            ->getArrayResult();
        if (!$rows) {
            return null;
        }

        return $rows[0][$fieldName];
    }
}
