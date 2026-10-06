<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Symfony\Contracts\Service\ResetInterface;

/**
 * A storage for configuration of all registered API resources.
 */
class ConfigBag implements ConfigBagInterface, RequestTypeAwareConfigBagInterface, ResetInterface
{
    private const ENTITIES = 'entities';

    private ConfigCache $configCache;
    private string $configFile;
    private string $requestTypeExpression;
    private ?array $config = null;

    public function __construct(ConfigCache $configCache, string $configFile, string $requestTypeExpression = '')
    {
        $this->configCache = $configCache;
        $this->configFile = $configFile;
        $this->requestTypeExpression = $requestTypeExpression;
    }

    #[\Override]
    public function getClassNames(string $version): array
    {
        $this->ensureInitialized();

        if (!isset($this->config[self::ENTITIES])) {
            return [];
        }

        return array_keys($this->config[self::ENTITIES]);
    }

    #[\Override]
    public function getConfig(string $className, string $version): ?array
    {
        $this->ensureInitialized();

        return $this->config[self::ENTITIES][$className] ?? null;
    }

    public function getRequestTypeExpression(): string
    {
        return $this->requestTypeExpression;
    }

    #[\Override]
    public function reset(): void
    {
        $this->config = null;
    }

    private function ensureInitialized(): void
    {
        if (null === $this->config) {
            $this->config = $this->configCache->getConfig($this->configFile);
        }
    }
}
