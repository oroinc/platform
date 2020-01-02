<?php

namespace Oro\Bundle\UIBundle\ContentProvider;

/**
 * Represents a service that provides a content for "hash navigation" response.
 */
interface ContentProviderInterface
{
    /**
     * Gets a content.
     *
     * @return mixed
     */
    public function getContent();
}
