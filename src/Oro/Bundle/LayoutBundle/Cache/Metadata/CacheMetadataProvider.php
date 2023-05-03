<?php

namespace Oro\Bundle\LayoutBundle\Cache\Metadata;

use Oro\Bundle\LayoutBundle\Exception\InvalidLayoutCacheMetadataException;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * General provider for layout cache metadata with the fallback to default provider.
 */
class CacheMetadataProvider implements CacheMetadataProviderInterface, ResetInterface
{
    private CacheMetadataProviderInterface $defaultProvider;

    /** @var iterable<CacheMetadataProviderInterface> */
    private iterable $providers;

    /** @var LayoutCacheMetadata[] */
    private array $metadataByBlockCacheKey = [];

    private LoggerInterface $logger;

    private bool $debug;

    public function __construct(
        CacheMetadataProviderInterface $defaultProvider,
        iterable $providers,
        LoggerInterface $logger,
        bool $debug
    ) {
        $this->defaultProvider = $defaultProvider;
        $this->providers = $providers;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    public function getCacheMetadata(BlockView $blockView, ContextInterface $context): ?LayoutCacheMetadata
    {
        $blockCacheKey = $blockView->vars['cache_key'];

        if (!array_key_exists($blockCacheKey, $this->metadataByBlockCacheKey)) {
            $this->metadataByBlockCacheKey[$blockCacheKey] = $this->doGetMetadata($blockView, $context);
        }

        return $this->metadataByBlockCacheKey[$blockCacheKey];
    }

    private function doGetMetadata(BlockView $blockView, ContextInterface $context): ?LayoutCacheMetadata
    {
        try {
            foreach ($this->providers as $provider) {
                $metadata = $provider->getCacheMetadata($blockView, $context);
                if (null !== $metadata) {
                    return $metadata;
                }
            }

            return $this->defaultProvider->getCacheMetadata($blockView, $context);
        } catch (InvalidLayoutCacheMetadataException $exception) {
            if ($this->debug) {
                throw $exception;
            }
            $this->logger->error(
                'Cannot cache the layout block "{id}", the cache metadata is invalid.',
                ['id' => $blockView->getId(), 'exception' => $exception]
            );

            return null;
        }
    }

    public function reset(): void
    {
        $this->metadataByBlockCacheKey = [];
    }
}
