<?php

namespace Oro\Bundle\SyncBundle\Content;

/**
 * Defines the contract for generating synchronization tags from various data sources.
 *
 * Tag generators are responsible for creating unique identifiers (tags) that represent content
 * in the system. These tags are used by the synchronization system to track which content needs
 * to be synchronized across connected clients when data changes. Implementations should support
 * different data formats and structures, allowing flexible tag generation strategies.
 */
interface TagGeneratorInterface
{
    const COLLECTION_SUFFIX = '_type_collection';

    /**
     * Is data format supported
     *
     * @param mixed $data
     *
     * @return bool
     */
    public function supports($data);

    /**
     * Generates tags for content
     *
     * @param mixed $data
     * @param bool  $includeCollectionTag - generate tag for collection views(e.g. grid pages)
     * @param bool  $processNestedData    - is processing nested data needed
     *
     * @return array
     */
    public function generate($data, $includeCollectionTag = false, $processNestedData = false);
}
