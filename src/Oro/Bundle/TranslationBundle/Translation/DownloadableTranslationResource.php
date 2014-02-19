<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Symfony\Component\Config\Resource\ResourceInterface;

class DownloadableTranslationResource implements ResourceInterface, DynamicResourceInterface
{
    const RESOURCE_PREFIX = 'downloadable_';

    /**
     * Constructor
     *
     * @param string                          $locale
     * @param DynamicTranslationMetadataCache $metadataCache
     */
    public function __construct(
        $locale,
        DynamicTranslationMetadataCache $metadataCache
    ) {
        $this->locale        = $locale;
        $this->metadataCache = $metadataCache;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($timestamp)
    {
        $cacheTimestamp = $this->metadataCache->getTimestamp($this->locale);

        return $cacheTimestamp === false || $cacheTimestamp < $timestamp;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return self::RESOURCE_PREFIX . $this->locale;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return self::RESOURCE_PREFIX . $this->locale;
    }
}
