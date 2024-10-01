<?php

declare(strict_types=1);

namespace Oro\Bundle\UIBundle\Tests\Functional\Stub;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderConfigurationProvider;
use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * Allows to override placeholder configuration in runtime.
 */
class PlaceholderConfigurationProviderDecorator extends PlaceholderConfigurationProvider
{
    protected PlaceholderConfigurationProvider $decorated;

    /** @var array[] */
    protected array $testPlaceholderItems = [];

    /** @var array[] */
    protected array $placeholderItemConfigs = [];

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(PlaceholderConfigurationProvider $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * Adds a placeholder item along with its config to a placeholder.
     */
    public function addPlaceholderItem(
        string $placeholderName,
        string $placeholderItem,
        ?array $itemConfig = null
    ): void {
        $this->testPlaceholderItems[$placeholderName][] = $placeholderItem;
        $this->placeholderItemConfigs[$placeholderItem] = $itemConfig;
    }

    #[\Override]
    public function getPlaceholderItems(string $placeholderName): ?array
    {
        return $this->testPlaceholderItems[$placeholderName] ?? $this->decorated->getPlaceholderItems($placeholderName);
    }

    #[\Override]
    public function getItemConfiguration(string $itemName): ?array
    {
        return $this->placeholderItemConfigs[$itemName] ?? $this->decorated->getItemConfiguration($itemName);
    }

    //region proxying all public method
    #[\Override]
    public function isCacheFresh(?int $timestamp): bool
    {
        return $this->decorated->isCacheFresh($timestamp);
    }

    #[\Override]
    public function getCacheTimestamp(): ?int
    {
        return $this->decorated->getCacheTimestamp();
    }

    #[\Override]
    public function clearCache(): void
    {
        $this->decorated->clearCache();
    }

    #[\Override]
    public function warmUpCache(): void
    {
        $this->decorated->warmUpCache();
    }

    #[\Override]
    public function ensureCacheWarmedUp(): void
    {
        $this->decorated->ensureCacheWarmedUp();
    }

    #[\Override]
    public function getCacheResource(): ResourceInterface
    {
        return $this->decorated->getCacheResource();
    }
    //endregion
}
