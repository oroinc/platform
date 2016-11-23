<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;

/**
 * The permission mask builder for 'Workflow' ACL extension.
 *
 * This class provides masks for the following permissions:
 *  VIEW_WORKFLOW, PERFORM_TRANSITIONS
 */
final class WorkflowMaskBuilder extends MaskBuilder
{
    // These access levels give a user access to own workflows.
    const MASK_VIEW_WORKFLOW_BASIC        = 1;   // 1 << 0
    const MASK_PERFORM_TRANSITIONS_BASIC  = 2;   // 1 << 1

    // These access levels give a user access to workflows in all business units are assigned to the user.
    const MASK_VIEW_WORKFLOW_LOCAL        = 4;   // 1 << 2
    const MASK_PERFORM_TRANSITIONS_LOCAL  = 8;   // 1 << 3

    // These access levels give a user access to workflows in all business units are assigned to the user
    // and all business units subordinate to business units are assigned to the user.
    const MASK_VIEW_WORKFLOW_DEEP         = 16;  // 1 << 4
    const MASK_PERFORM_TRANSITIONS_DEEP   = 32;  // 1 << 5

    // These access levels give a user access to all workflows within the organization,
    // regardless of the business unit hierarchical level to which the domain object belongs
    // or the user is assigned to.
    const MASK_VIEW_WORKFLOW_GLOBAL       = 64;  // 1 << 6
    const MASK_PERFORM_TRANSITIONS_GLOBAL = 128; // 1 << 7

    // These access levels give a user access to all workflows within the system.
    const MASK_VIEW_WORKFLOW_SYSTEM       = 256; // 1 << 8
    const MASK_PERFORM_TRANSITIONS_SYSTEM = 512; // 1 << 9

    // Some useful groups of bitmasks

    const GROUP_BASIC  = 3;   // 0x3
    const GROUP_LOCAL  = 12;  // 0xC
    const GROUP_DEEP   = 48;  // 0x30
    const GROUP_GLOBAL = 192; // 0xC0
    const GROUP_SYSTEM = 768; // 0x300

    const GROUP_VIEW_WORKFLOW       = 341;  // 0x155
    const GROUP_PERFORM_TRANSITIONS = 682;  // 0x2AA
    const GROUP_NONE                = 0;    // 0x0
    const GROUP_ALL                 = 1023; // 0x3FF

    const CODE_VIEW_WORKFLOW       = 'V';
    const CODE_PERFORM_TRANSITIONS = 'P';

    const PATTERN_ALL_OFF = '(PV) system:.. global:.. deep:.. local:.. basic:..';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
}
