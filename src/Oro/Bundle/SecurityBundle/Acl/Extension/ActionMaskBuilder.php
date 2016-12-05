<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;

/**
 * The permission mask builder for 'Action' ACL extension
 */
final class ActionMaskBuilder extends MaskBuilder
{
    const SERVICE_BITS        = -2; // 0xFFFFFFFE
    const REMOVE_SERVICE_BITS = 1;  // 0x00000001

    const MASK_EXECUTE = 1;

    // Some useful groups of bitmasks
    const GROUP_NONE = 0;
    const GROUP_ALL  = 1;

    const CODE_EXECUTE = 'E';

    const PATTERN_ALL_OFF = '(E) .';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
}
