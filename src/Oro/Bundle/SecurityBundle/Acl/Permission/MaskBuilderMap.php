<?php

namespace Oro\Bundle\SecurityBundle\Acl\Permission;

/**
 * A storage for permissions supported by a mask builder.
 */
class MaskBuilderMap
{
    /** @var array [name => value, ...] */
    public $all;

    /** @var array [name => value, ...] */
    public $group;

    /** @var array [name => value, ...] */
    public $permission;
}
