<?php

namespace Oro\Bundle\EntityBundle\ORM;

/**
 * Container for ORM events in addition to ORM events declared in {@see Doctrine\ORM\Events}.
 */
final class Events
{
    /**
     * Private constructor. This class is not meant to be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * The preClose event occurs when the EntityManager#close() operation is invoked,
     * before EntityManager#clear() is invoked.
     *
     * @var string
     */
    // @codingStandardsIgnoreStart
    const preClose = 'preClose';
    // @codingStandardsIgnoreEnd
}
