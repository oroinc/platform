<?php

namespace Oro\Bundle\TranslationBundle\Api;

use Psr\Container\ContainerInterface;

/**
 * Contains resolvers for all predefined language codes used in API.
 */
class PredefinedLanguageCodeResolverRegistry
{
    /** @var string[] */
    private array $predefinedLanguageCodes;
    private ContainerInterface $resolverContainer;

    /**
     * @param string[]           $predefinedLanguageCodes
     * @param ContainerInterface $resolverContainer
     */
    public function __construct(array $predefinedLanguageCodes, ContainerInterface $resolverContainer)
    {
        $this->predefinedLanguageCodes = $predefinedLanguageCodes;
        $this->resolverContainer = $resolverContainer;
    }

    /**
     * Gets a language code corresponds to the given predefined language code.
     * If the given predefined language code cannot be resolved returns NULL.
     */
    public function resolve(string $value): ?string
    {
        if (!$this->resolverContainer->has($value)) {
            return null;
        }

        return $this->instantiateResolver($value)->resolve();
    }

    /**
     * Gets descriptions of all predefined predefined language codes used in API.
     * These descriptions are used in auto-generated documentation, including API sandbox.
     *
     * @return string[]
     */
    public function getDescriptions(): array
    {
        $descriptions = [];
        foreach ($this->predefinedLanguageCodes as $predefinedLanguageCode) {
            $descriptions[] = $this->instantiateResolver($predefinedLanguageCode)->getDescription();
        }

        return $descriptions;
    }

    private function instantiateResolver(string $serviceId): PredefinedLanguageCodeResolverInterface
    {
        return $this->resolverContainer->get($serviceId);
    }
}
