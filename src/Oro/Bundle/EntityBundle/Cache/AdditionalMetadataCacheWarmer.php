<?php

namespace Oro\Bundle\EntityBundle\Cache;

use Oro\Bundle\EntityBundle\ORM\Mapping\AdditionalMetadataProvider;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class AdditionalMetadataCacheWarmer implements CacheWarmerInterface
{
    /** @var AdditionalMetadataProvider */
    protected $additionalMetadataProvider;

    /**
     * @param AdditionalMetadataProvider $additionalMetadataProvider
     */
    public function __construct(AdditionalMetadataProvider $additionalMetadataProvider)
    {
        $this->additionalMetadataProvider = $additionalMetadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->additionalMetadataProvider->warmUpMetadata();
    }
}
