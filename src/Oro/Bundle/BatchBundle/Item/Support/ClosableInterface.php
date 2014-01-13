<?php

namespace Oro\Bundle\BatchBundle\Item\Support;

/**
 * Represents a resource (for example a file) which should be closed to release allocated resources.
 * The close method is invoked to release resources that the object is holding (such as open file).
 */
interface ClosableInterface
{
    /**
     * Releases system resources that the object is holding.
     */
    public function close();
}
