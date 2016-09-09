<?php

namespace Oro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;

/**
 * The permission mask builder for 'Field' ACL extension.
 *
 * This class provides masks for the following permissions:
 *  VIEW, CREATE, EDIT
 */
final class FieldMaskBuilder extends MaskBuilder
{
    const IDENTITY = 0;
    const SERVICE_BITS          = -2048;  // 0xFFFFF800
    const REMOVE_SERVICE_BITS   = 2047;   // 0x000007FF

    // These access levels give a user access to own records fields and objects fields that are shared with the user.
    const MASK_VIEW_BASIC       = 1;      // 1 << 0     + IDENTITY
    const MASK_EDIT_BASIC       = 2;      // 1 << 1     + IDENTITY

    // These access levels give a user access to records fields in all business units are assigned to the user.
    const MASK_VIEW_LOCAL       = 4;      // 1 << 2     + IDENTITY
    const MASK_EDIT_LOCAL       = 8;      // 1 << 3     + IDENTITY

    // These access levels give a user access to records fields in all business units are assigned to the user
    // and all business units subordinate to business units are assigned to the user.
    const MASK_VIEW_DEEP        = 16;     // 1 << 4     + IDENTITY
    const MASK_EDIT_DEEP        = 32;     // 1 << 5     + IDENTITY

    // These access levels give a user access to all records fields within the organization,
    // regardless of the business unit hierarchical level to which the domain object belongs
    // or the user is assigned to.
    const MASK_VIEW_GLOBAL      = 64;     // 1 << 6     + IDENTITY
    const MASK_EDIT_GLOBAL      = 128;    // 1 << 7     + IDENTITY

    // These access levels give a user access to all records fields within the system.
    const MASK_VIEW_SYSTEM      = 256;    // 1 << 8     + IDENTITY
    const MASK_CREATE_SYSTEM    = 512;    // 1 << 9     + IDENTITY
    const MASK_EDIT_SYSTEM      = 1024;   // 1 << 10    + IDENTITY

    // Some useful groups of bitmasks

    const GROUP_BASIC           = 3;      // 0x3       + IDENTITY
    const GROUP_LOCAL           = 12;     // 0xC       + IDENTITY
    const GROUP_DEEP            = 48;     // 0x30      + IDENTITY
    const GROUP_GLOBAL          = 192;    // 0xC0      + IDENTITY
    const GROUP_SYSTEM          = 1792;   // 0x700     + IDENTITY

    const GROUP_VIEW            = 341;    // 0x155     + IDENTITY
    const GROUP_CREATE          = 512;    // 0x200     + IDENTITY
    const GROUP_EDIT            = 1194;   // 0x4AA     + IDENTITY
    const GROUP_NONE            = self::IDENTITY;
    const GROUP_ALL             = 2047;   // 0x7FF     + IDENTITY

    const CODE_VIEW             = 'V';
    const CODE_CREATE           = 'C';
    const CODE_EDIT             = 'E';

    const PATTERN_ALL_OFF       = '(ECV) .. system:...... global:...... deep:...... local:...... basic:......';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
}
