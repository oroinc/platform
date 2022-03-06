<?php

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

use Symfony\Contracts\Cache\CacheInterface;

/**
 * The base class to provides cached configuration for the sandboxed TWIG templates renderer.
 */
class TemplateRendererConfigProvider implements TemplateRendererConfigProviderInterface
{
    private const PROPERTY_PATH     = 'property_path';
    private const DEFAULT_FORMATTER = 'default_formatter';
    private const RELATED_ENTITY    = 'related_entity_name';

    private VariablesProvider $variablesProvider;
    private CacheInterface $cache;
    private string $configCacheKey;
    private ?array $configuration = null;
    private ?array $systemVariables = null;
    private array $entityVariableProcessors = [];

    public function __construct(VariablesProvider $variablesProvider, CacheInterface $cache, string $configCacheKey)
    {
        $this->variablesProvider = $variablesProvider;
        $this->cache = $cache;
        $this->configCacheKey = $configCacheKey;
    }

    public function getConfiguration(): array
    {
        if (null === $this->configuration) {
            $this->configuration = $this->cache->get($this->configCacheKey, function () {
                return $this->loadConfiguration();
            });
        }

        return $this->configuration;
    }

    public function getSystemVariableValues(): array
    {
        if (null === $this->systemVariables) {
            $this->systemVariables = $this->variablesProvider->getSystemVariableValues();
        }

        return $this->systemVariables;
    }

    public function getEntityVariableProcessors(string $entityClass): array
    {
        if (!isset($this->entityVariableProcessors[$entityClass])) {
            $this->entityVariableProcessors[$entityClass] =
                $this->variablesProvider->getEntityVariableProcessors($entityClass);
        }

        return $this->entityVariableProcessors[$entityClass];
    }

    public function clearCache(): void
    {
        $this->cache->delete($this->configCacheKey);
        $this->configuration = null;
        $this->systemVariables = null;
        $this->entityVariableProcessors = [];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function loadConfiguration(): array
    {
        $configuration = [];
        $configuration[self::DEFAULT_FORMATTERS] = [];
        $allGetters = $this->variablesProvider->getEntityVariableGetters();
        foreach ($allGetters as $className => $getters) {
            $properties = [];
            $methods = [];
            $accessors = [];
            $defaultFormatters = [];
            foreach ($getters as $varName => $getter) {
                if (!$getter) {
                    $properties[] = $varName;
                    $accessors[$varName] = null;
                } elseif (\is_string($getter)) {
                    $methods[] = $getter;
                    $accessors[$varName] = $getter;
                } else {
                    if (empty($getter[self::PROPERTY_PATH])) {
                        $properties[] = $varName;
                        $accessors[$varName] = null;
                    } else {
                        $methods[] = $getter[self::PROPERTY_PATH];
                        $accessors[$varName] = $getter[self::PROPERTY_PATH];
                    }
                    if (!empty($getter[self::DEFAULT_FORMATTER])) {
                        $defaultFormatters[$varName] = $getter[self::DEFAULT_FORMATTER];
                    }
                    // Register related class in methods to allow __toString
                    if (!empty($getter[self::RELATED_ENTITY])) {
                        if (!array_key_exists(self::METHODS, $configuration)) {
                            $configuration[self::METHODS]= [];
                        }
                        $relatedClass = $getter[self::RELATED_ENTITY];
                        if (!array_key_exists($relatedClass, $configuration[self::METHODS])) {
                            $configuration[self::METHODS][$relatedClass] = [];
                        }
                    }
                }
            }

            $configuration[self::PROPERTIES][$className] = $properties;
            $configuration[self::METHODS][$className] = $methods;
            $configuration[self::ACCESSORS][$className] = $accessors;

            if ($defaultFormatters) {
                $configuration[self::DEFAULT_FORMATTERS][$className] = $defaultFormatters;
            }
        }

        return $configuration;
    }
}
