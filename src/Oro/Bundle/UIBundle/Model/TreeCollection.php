<?php

namespace Oro\Bundle\UIBundle\Model;

class TreeCollection
{
    /** @var TreeItem[] */
    public $source = [];

    /** @var TreeItem */
    public $target;

    /** @var TreeItem */
    public $createRedirect;
}
