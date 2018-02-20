<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;

class OrmTranslationResource implements ResourceInterface, DynamicResourceInterface, SelfCheckingResourceInterface
{
    /**
     * @var string
     */
    protected $locale;

    /**
     * @var DynamicTranslationMetadataCache
     */
    protected $metadataCache;

    /**
     * Constructor
     *
     * @param string                      $locale
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
        return Translation::class . $this->locale;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return Translation::class . $this->locale;
    }
}
