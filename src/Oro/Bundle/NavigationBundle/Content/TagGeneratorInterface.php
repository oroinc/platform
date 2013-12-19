<?php

namespace Oro\Bundle\NavigationBundle\Content;

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
     *
     * @return array
     */
    public function generate($data, $includeCollectionTag = false);
}
