<?php

namespace Oro\Bundle\EntityBundle\ORM;

/**
 * Container for ORM events in addition to ORM events declared in {@see \Doctrine\ORM\Events}.
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
    // phpcs:disable
    public const preClose = 'preClose';
    // phpcs:enable
    /**
     * The preClear event occurs when the EntityManager#clear() operation is invoked,
     * before onClear event, i.e. before the actual clearing.
     *
     * @var string
     */
    // phpcs:disable
    public const preClear = 'preClear';
    // phpcs:enable
}
