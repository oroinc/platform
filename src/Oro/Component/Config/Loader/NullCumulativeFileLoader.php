<?php

namespace Oro\Component\Config\Loader;

/**
 * The implementation of a cumulative file loader that does not load a content of a file.
 * This loader can be used if you need to find only paths of cumulative files.
 */
class NullCumulativeFileLoader extends CumulativeFileLoader
{
    /**
     * {@inheritDoc}
     */
    protected function loadFile($file)
    {
        return null;
    }
}
