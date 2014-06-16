<?php

namespace Oro\Bundle\OrganizationBundle\Autocomplete;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;

use Oro\Bundle\FormBundle\Autocomplete\FullNameSearchHandler;

class OrganizationSearchHandler extends FullNameSearchHandler
{
    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @param CacheManager $cacheManager
     * @param string $entityName
     * @param array $properties
     */
    public function __construct(CacheManager $cacheManager, $entityName, array $properties)
    {
        $this->cacheManager = $cacheManager;
        parent::__construct($entityName, $properties);
    }
}