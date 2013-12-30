<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Symfony\Component\Config\Resource\ResourceInterface;
use Oro\Bundle\TranslationBundle\Entity\Translation;

class OrmTranslationResource implements ResourceInterface, DynamicResourceInterface
{
    /**
     * @var string
     */
    protected $locale;

    /**
     * @var OrmTranslationMetadataCache
     */
    protected $metadataCache;

    /**
     * Constructor
     *
     * @param string                      $locale
     * @param OrmTranslationMetadataCache $metadataCache
     */
    public function __construct(
        $locale,
        OrmTranslationMetadataCache $metadataCache
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
        return Translation::ENTITY_NAME . $this->locale;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return Translation::ENTITY_NAME . $this->locale;
    }
}
