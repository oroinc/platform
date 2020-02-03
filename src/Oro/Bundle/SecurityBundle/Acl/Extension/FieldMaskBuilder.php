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
    public const SERVICE_BITS        = -2048; // 0xFFFFF800
    public const REMOVE_SERVICE_BITS = 2047;  // 0x000007FF

    // These access levels give a user access to fields of own records.
    public const MASK_VIEW_BASIC    = 1;    // 1 << 0
    public const MASK_EDIT_BASIC    = 2;    // 1 << 1

    // These access levels give a user access to fields of records in all business units are assigned to the user.
    public const MASK_VIEW_LOCAL    = 4;    // 1 << 2
    public const MASK_EDIT_LOCAL    = 8;    // 1 << 3

    // These access levels give a user access to fields of records in all business units are assigned to the user
    // and all business units subordinate to business units are assigned to the user.
    public const MASK_VIEW_DEEP     = 16;   // 1 << 4
    public const MASK_EDIT_DEEP     = 32;   // 1 << 5

    // These access levels give a user access to all fields of records within the organization,
    // regardless of the business unit hierarchical level to which the domain object belongs
    // or the user is assigned to.
    public const MASK_VIEW_GLOBAL   = 64;   // 1 << 6
    public const MASK_EDIT_GLOBAL   = 128;  // 1 << 7

    // These access levels give a user access to all fields of records within the system.
    public const MASK_VIEW_SYSTEM   = 256;  // 1 << 8
    public const MASK_CREATE_SYSTEM = 512;  // 1 << 9
    public const MASK_EDIT_SYSTEM   = 1024; // 1 << 10

    public const GROUP_BASIC  = 3;    // 0x3
    public const GROUP_LOCAL  = 12;   // 0xC
    public const GROUP_DEEP   = 48;   // 0x30
    public const GROUP_GLOBAL = 192;  // 0xC0
    public const GROUP_SYSTEM = 1792; // 0x700

    public const GROUP_VIEW   = 341;  // 0x155
    public const GROUP_CREATE = 512;  // 0x200
    public const GROUP_EDIT   = 1194; // 0x4AA
    public const GROUP_NONE   = 0;
    public const GROUP_ALL    = 2047; // 0x7FF

    public const CODE_VIEW   = 'V';
    public const CODE_CREATE = 'C';
    public const CODE_EDIT   = 'E';

    protected const PATTERN_ALL_OFF = '(ECV) system:... global:.. deep:.. local:.. basic:..';

    public function __construct()
    {
        parent::__construct();
    }
}
