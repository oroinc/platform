<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;

/**
 * The permission mask builder for 'Action' ACL extension
 */
final class ActionMaskBuilder extends MaskBuilder
{
    public const SERVICE_BITS        = -2; // 0xFFFFFFFE
    public const REMOVE_SERVICE_BITS = 1;  // 0x00000001

    public const MASK_EXECUTE = 1;

    public const GROUP_NONE = 0;
    public const GROUP_ALL  = 1;

    public const CODE_EXECUTE = 'E';

    protected const PATTERN_ALL_OFF = '(E) .';

    public function __construct()
    {
        parent::__construct();
    }
}
