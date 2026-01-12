<?php

namespace Oro\Component\Layout;

/**
 * Defines the contract for caching and retrieving serialized block view hierarchies.
 *
 * This cache stores and retrieves serialized block view data based on the layout context,
 * allowing for performance optimization by avoiding repeated block view construction.
 */
interface BlockViewCacheInterface
{
    /**
     * Puts BlockView serialized data into the cache.
     */
    public function save(ContextInterface $context, BlockView $rootView);

    /**
     * Gets BlockView deserialized data from the cache.
     *
     * @param ContextInterface $context
     *
     * @return BlockView
     */
    public function fetch(ContextInterface $context);

    /**
     * Deletes all cache entries for 'oro_layout_block_view' cache namespace
     */
    public function reset();
}
