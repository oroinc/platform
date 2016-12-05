<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;

/**
 * The permission mask builder for 'WorkflowTransition' ACL extension.
 *
 * This class provides masks for the following permissions:
 *  PERFORM_TRANSITION
 */
final class WorkflowTransitionMaskBuilder extends MaskBuilder
{
    const SERVICE_BITS        = -32; // 0xFFFFFFE0
    const REMOVE_SERVICE_BITS = 31;  // 0x0000001F

    // These access levels give a user access to own workflow transitions.
    const MASK_PERFORM_TRANSITION_BASIC  = 1;  // 1 << 1

    // These access levels give a user access to workflow transitions in all business units are assigned to the user.
    const MASK_PERFORM_TRANSITION_LOCAL  = 2;  // 1 << 2

    // These access levels give a user access to workflow transitions in all business units are assigned to the user
    // and all business units subordinate to business units are assigned to the user.
    const MASK_PERFORM_TRANSITION_DEEP   = 4;  // 1 << 3

    // These access levels give a user access to all workflow transitions within the organization,
    // regardless of the business unit hierarchical level to which the domain object belongs
    // or the user is assigned to.
    const MASK_PERFORM_TRANSITION_GLOBAL = 8;  // 1 << 4

    // These access levels give a user access to all workflow transitions within the system.
    const MASK_PERFORM_TRANSITION_SYSTEM = 16; // 1 << 5

    // Some useful groups of bitmasks

    const GROUP_BASIC  = 1;  // 0x1
    const GROUP_LOCAL  = 2;  // 0x2
    const GROUP_DEEP   = 4;  // 0x2
    const GROUP_GLOBAL = 8;  // 0x8
    const GROUP_SYSTEM = 16; // 0x10

    const GROUP_PERFORM_TRANSITION = 31; // 0x1F
    const GROUP_NONE               = 0;  // 0x0
    const GROUP_ALL                = 31; // 0x1F

    const CODE_PERFORM_TRANSITION = 'P';

    const PATTERN_ALL_OFF = '(P) system:. global:. deep:. local:. basic:.';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
}
