<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\Analyzer;

use Oro\Bundle\EntityBundle\Twig\Sandbox\VariablesProvider;
use Oro\Component\Config\Cache\ClearableConfigCacheInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Provides and caches a map of known entity variables with their related entity classes,
 * aggregated from all registered {@see \Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface}
 * providers via {@see VariablesProvider::getEntityVariableDefinitions()}.
 *
 * The map structure is: [className => [varName => relatedClass|null]]
 */
class EntityVariablesProvider implements ClearableConfigCacheInterface
{
    public function __construct(
        private readonly VariablesProvider $variablesProvider,
        private readonly CacheInterface $cache,
        private readonly string $cacheKey,
    ) {
    }

    /**
     * Returns the map of known variables and their related entity classes for the given class,
     * or null when the class has no registered variables.
     *
     * @return array<string, string|null>|null [varName => relatedClass|null]
     */
    public function getClassVariables(string $className): ?array
    {
        $data = $this->cache->get($this->cacheKey, fn () => $this->build());

        return $data[$className] ?? null;
    }

    /**
     * Removes all entries from the shared cache.
     */
    public function clearCache(): void
    {
        $this->cache->delete($this->cacheKey);
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function build(): array
    {
        $data = [];

        foreach ($this->variablesProvider->getEntityVariableDefinitions() as $className => $variables) {
            foreach ($variables as $varName => $definition) {
                if (!is_string($varName)) {
                    continue;
                }

                $data[$className][$varName] = is_array($definition) && isset($definition['related_entity_name'])
                    ? $definition['related_entity_name']
                    : null;
            }
        }

        return $data;
    }
}
