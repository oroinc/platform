<?php

namespace Oro\Bundle\LayoutBundle\Cache\Metadata;

use Oro\Bundle\LayoutBundle\Exception\InvalidLayoutCacheMetadataException;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextInterface;

/**
 * Cache metadata provider that reads metadata from block options.
 */
class DefaultCacheMetadataProvider implements CacheMetadataProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getCacheMetadata(BlockView $blockView, ContextInterface $context): ?LayoutCacheMetadata
    {
        $metadata = $blockView->vars['cache'];
        if (!$metadata) {
            return null;
        }

        if (true === $metadata) {
            return new LayoutCacheMetadata();
        }

        if (!\is_array($metadata)) {
            throw new InvalidLayoutCacheMetadataException(
                sprintf(
                    'The value of the "cache" block option is expected to be an array, boolean, or null but got "%s".',
                    \is_object($metadata) ? \get_class($metadata) : \gettype($metadata)
                )
            );
        }

        if (\array_key_exists('if', $metadata) && false == $metadata['if']) {
            return null;
        }

        $maxAge = $metadata['maxAge'] ?? null;
        $varyBy = $metadata['varyBy'] ?? [];
        $tags = $metadata['tags'] ?? [];

        $metadata = (new LayoutCacheMetadata())
            ->setMaxAge($maxAge)
            ->setTags($tags)
            ->setVaryBy($varyBy);

        return $metadata;
    }
}
