<?php

namespace Oro\Component\Layout;

interface BlockViewCacheInterface
{
    /**
     * Puts BlockView serialized data into the cache.
     *
     * @param ContextInterface $context
     * @param BlockView $rootView
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
