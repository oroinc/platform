<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

class GlobalScopeManagerTest extends AbstractScopeManagerTestCase
{
    /** @var GlobalScopeManager */
    protected $manager;

    /**
     * {@inheritdoc}
     *
     * @return GlobalScopeManager
     */
    protected function createManager(ManagerRegistry $doctrine, CacheProvider $cache, EventDispatcher $eventDispatcher)
    {
        return new GlobalScopeManager($doctrine, $cache, $eventDispatcher);
    }

    /**
     * {@inheritdoc}
     */
    protected function getScopedEntityName()
    {
        return 'app';
    }
}
