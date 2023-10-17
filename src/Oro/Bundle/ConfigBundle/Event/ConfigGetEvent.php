<?php

namespace Oro\Bundle\ConfigBundle\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The event that is fired when a configuration option value is retrieved.
 * It allows to make an additional transformation of the configuration option value.
 */
class ConfigGetEvent extends Event
{
    public const NAME = 'oro_config.get';

    private ConfigManager $configManager;
    private string $key;
    private mixed $value;
    private bool $full;
    private string $scope;
    private int $scopeId;

    public function __construct(
        ConfigManager $configManager,
        string $key,
        mixed $value,
        bool $full,
        string $scope,
        int $scopeId
    ) {
        $this->configManager = $configManager;
        $this->key = $key;
        $this->value = $value;
        $this->full = $full;
        $this->scope = $scope;
        $this->scopeId = $scopeId;
    }

    public function getConfigManager(): ConfigManager
    {
        return $this->configManager;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function isFull(): bool
    {
        return $this->full;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getScopeId(): int
    {
        return $this->scopeId;
    }
}
