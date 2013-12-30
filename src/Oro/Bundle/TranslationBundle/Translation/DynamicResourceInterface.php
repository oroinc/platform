<?php

namespace Oro\Bundle\TranslationBundle\Translation;

/**
 * A translation resource which can be changed must implement DynamicResourceInterface
 * Example of such resource may be translations stored in a database
 */
interface DynamicResourceInterface
{
    /**
     * Returns true if the resource has not been updated since the given timestamp.
     *
     * @param integer $timestamp The last time the resource was loaded
     * @return Boolean true if the resource has not been updated, false otherwise
     */
    public function isFresh($timestamp);
}
