<?php

namespace Oro\Bundle\UIBundle\Model;

/**
 * Data transfer object for tree move operations.
 *
 * Holds the source items being moved, the target location in the tree hierarchy,
 * and a flag indicating whether a redirect should be created when the move operation
 * results in a slug change.
 */
class TreeCollection
{
    /** @var TreeItem[] */
    public $source = [];

    /** @var TreeItem */
    public $target;

    /** @var TreeItem */
    public $createRedirect;
}
