<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

/**
 * Marks all Doctrine ORM entity listeners as lazy.
 */
class LazyDoctrineOrmListenersPass extends LazyDoctrineListenersPass
{
    /**
     * @return string
     */
    protected function getListenerTagName()
    {
        return 'doctrine.orm.entity_listener';
    }
}
