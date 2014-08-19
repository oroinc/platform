<?php

namespace Oro\Bundle\EntityExtendBundle\EntityConfig;

class ExtendScope
{
    const STATE_NEW     = 'New';
    const STATE_UPDATED = 'Requires update';
    const STATE_ACTIVE  = 'Active';
    const STATE_DELETED = 'Deleted';

    /**
     * The system extend entities and fields means that they cannot be removed by an administrator
     * and a developer who created them should take care about UI for them
     */
    const OWNER_SYSTEM = 'System';

    /**
     * The system is fully responsible how the custom extend entities and fields are used on UI
     */
    const OWNER_CUSTOM = 'Custom';
}
