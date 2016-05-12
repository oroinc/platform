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
    const SERVICE_BITS          = -1073741824;  // 0xC0000000
    const REMOVE_SERVICE_BITS   = 1073741823;   // 0x3FFFFFFF

    // These access levels give a user access to own records and objects that are shared with the user.
    const MASK_VIEW_BASIC         = 1;          // 1 << 0     + IDENTITY
    const MASK_CREATE_BASIC       = 2;          // 1 << 1     + IDENTITY
    const MASK_EDIT_BASIC         = 4;          // 1 << 2     + IDENTITY

    // These access levels give a user access to records in all business units are assigned to the user.
    const MASK_VIEW_LOCAL         = 64;         // 1 << 6     + IDENTITY
    const MASK_CREATE_LOCAL       = 128;        // 1 << 7     + IDENTITY
    const MASK_EDIT_LOCAL         = 256;        // 1 << 8     + IDENTITY

    // These access levels give a user access to records in all business units are assigned to the user
    // and all business units subordinate to business units are assigned to the user.
    const MASK_VIEW_DEEP          = 4096;       // 1 << 12    + IDENTITY
    const MASK_CREATE_DEEP        = 8192;       // 1 << 13    + IDENTITY
    const MASK_EDIT_DEEP          = 16384;      // 1 << 14    + IDENTITY

    // These access levels give a user access to all records within the organization,
    // regardless of the business unit hierarchical level to which the domain object belongs
    // or the user is assigned to.
    const MASK_VIEW_GLOBAL        = 262144;     // 1 << 18    + IDENTITY
    const MASK_CREATE_GLOBAL      = 524288;     // 1 << 19    + IDENTITY
    const MASK_EDIT_GLOBAL        = 1048576;    // 1 << 20    + IDENTITY

    // These access levels give a user access to all records within the system.
    const MASK_VIEW_SYSTEM        = 16777216;   // 1 << 24    + IDENTITY
    const MASK_CREATE_SYSTEM      = 33554432;   // 1 << 25    + IDENTITY
    const MASK_EDIT_SYSTEM        = 67108864;   // 1 << 26    + IDENTITY

    // Some useful groups of bitmasks
    const GROUP_BASIC             = 63;         // 0x3F       + IDENTITY
    const GROUP_LOCAL             = 4032;       // 0xFC0      + IDENTITY
    const GROUP_DEEP              = 258048;     // 0x3F000    + IDENTITY
    const GROUP_GLOBAL            = 16515072;   // 0xFC0000   + IDENTITY
    const GROUP_SYSTEM            = 1056964608; // 0x3F000000 + IDENTITY
    const GROUP_VIEW              = 17043521;   // 0x1041041  + IDENTITY
    const GROUP_CREATE            = 34087042;   // 0x2082082  + IDENTITY
    const GROUP_EDIT              = 68174084;   // 0x4104104  + IDENTITY
    const GROUP_CRUD_SYSTEM       = 251658240;  // 0xF000000  + IDENTITY
    const GROUP_NONE              = self::IDENTITY;
    const GROUP_ALL               = 1073741823; // 0x3FFFFFFF + IDENTITY

    const CODE_VIEW         = 'V';
    const CODE_CREATE       = 'C';
    const CODE_EDIT         = 'E';

    const PATTERN_ALL_OFF   = '(ECV) .. system:...... global:...... deep:...... local:...... basic:......';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
}
