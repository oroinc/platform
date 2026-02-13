<?php

namespace Oro\Bundle\ThemeBundle\Provider;

/**
 * Provides theme configuration types and labels for themes.
 */
class ThemeConfigurationTypeProvider
{
    /** @var string[] */
    private array $types = [];

    /** @var array['label'=>'type'] */
    private array $labelsAndTypes = [];

    /**
     * @param iterable|ThemeConfigurationTypeProviderInterface[] $typeProviders
     */
    public function __construct(
        private iterable $typeProviders
    ) {
    }

    public function getTypes(): array
    {
        if (empty($this->types)) {
            foreach ($this->typeProviders as $typeProvider) {
                $this->types[] = $typeProvider->getType();
            }
        }

        return $this->types;
    }

    public function getLabelsAndTypes(): array
    {
        if (empty($this->labelsAndTypes)) {
            foreach ($this->typeProviders as $typeProvider) {
                $this->labelsAndTypes[$typeProvider->getLabel()] = $typeProvider->getType();
            }
        }

        return $this->labelsAndTypes;
    }
}
