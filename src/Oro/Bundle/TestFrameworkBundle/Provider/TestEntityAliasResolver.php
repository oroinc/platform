<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

use Doctrine\Common\Cache\Cache;

use Symfony\Component\Debug\BufferingLogger;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityAliasLoader;

/**
 * This class is used for testing that all entity aliases can be loaded without any errors.
 */
class TestEntityAliasResolver extends EntityAliasResolver
{
    /** @var BufferingLogger */
    private $bufferingLogger;

    /**
     * @param EntityAliasLoader $loader
     * @param Cache             $cache
     */
    public function __construct(EntityAliasLoader $loader, Cache $cache)
    {
        $this->bufferingLogger = new BufferingLogger();
        parent::__construct($loader, $cache, $this->bufferingLogger, true);
    }

    /**
     * @return array
     */
    public function popLogs()
    {
        return $this->bufferingLogger->cleanLogs();
    }
}
