<?php

namespace Oro\Component\Config\Loader;

/**
 * Provides iteration over a collection of cumulative resource loaders.
 *
 * This class extends {@see \ArrayIterator} to manage and iterate through multiple
 * cumulative resource loaders in a standardized way.
 */
class CumulativeResourceLoaderCollection extends \ArrayIterator
{
}
