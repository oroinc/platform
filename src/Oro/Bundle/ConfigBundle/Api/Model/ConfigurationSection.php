<?php

namespace Oro\Bundle\ConfigBundle\Api\Model;

/**
 * Represents a system configuration section.
 */
class ConfigurationSection
{
    private string $id;
    private array $options = [];

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return ConfigurationOption[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param ConfigurationOption[] $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}
