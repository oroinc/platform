<?php

namespace Oro\Bundle\EntityExtendBundle\EntityConfig;

class ExtendScope
{
    const STATE_NEW     = 'New';
    const STATE_UPDATED = 'Requires update';
    const STATE_ACTIVE  = 'Active';
    const STATE_DELETED = 'Deleted';

    const OWNER_SYSTEM = 'System';
    const OWNER_CUSTOM = 'Custom';
}
