<?php

namespace Oro\Bundle\LayoutBundle\Cache\Metadata;

use Oro\Bundle\LayoutBundle\Exception\InvalidLayoutCacheMetadataException;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Component\Layout\BlockView;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * General provider for layout cache metadata with the fallback to default provider.
 */
class CacheMetadataProvider implements ResetInterface
{
    /**
     * @var CacheMetadataProviderInterface
     */
    private $defaultProvider;

    /**
     * @var iterable|CacheMetadataProviderInterface[]
     */
    private $providers;

    /**
     * @var LayoutContextHolder
     */
    private $contextHolder;

    /**
     * @var LayoutCacheMetadata[]
     */
    private $metadataByBlockCacheKey = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param CacheMetadataProviderInterface            $defaultProvider
     * @param CacheMetadataProviderInterface[]|iterable $providers
     * @param LayoutContextHolder                       $contextHolder
     * @param LoggerInterface                           $logger
     * @param bool                                      $debug
     */
    public function __construct(
        CacheMetadataProviderInterface $defaultProvider,
        iterable $providers,
        LayoutContextHolder $contextHolder,
        LoggerInterface $logger,
        bool $debug
    ) {
        $this->defaultProvider = $defaultProvider;
        $this->providers = $providers;
        $this->contextHolder = $contextHolder;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheMetadata(BlockView $blockView): ?LayoutCacheMetadata
    {
        $blockCacheKey = $blockView->vars['cache_key'];

        if (!array_key_exists($blockCacheKey, $this->metadataByBlockCacheKey)) {
            $this->metadataByBlockCacheKey[$blockCacheKey] = $this->doGetMetadata($blockView);
        }

        return $this->metadataByBlockCacheKey[$blockCacheKey];
    }

    private function doGetMetadata(BlockView $blockView): ?LayoutCacheMetadata
    {
        $context = $this->contextHolder->getContext();

        if (!$context) {
            return null;
        }

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
