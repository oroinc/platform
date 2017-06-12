<?php

namespace Oro\Bundle\EntityExtendBundle\EntityConfig;

class ExtendScope
{
    /**
     * New entity or field is registered in entity config and database schema update is required
     * to apply changes to a database and update Doctrine's metadata and proxies.
     */
    const STATE_NEW = 'New';

    /**
     * An entity or field properties were modified and database schema update is required
     * to apply this changes to a database and update Doctrine's metadata and proxies.
     */
    const STATE_UPDATE = 'Requires update';

    /**
     * An entity or field was marked as "to be deleted" and database schema update is required
     * to apply this changes to a database and update Doctrine's metadata and proxies.
     *
     * After database schema is updated the deleted entity or field is still available and can be
     * restored if it will be needed. To check if an entity or field is deleted use extend.is_deleted
     */
    const STATE_DELETE = 'Deleted';

    /**
     * The previously deleted entity (not implemented yet) or field was marked as "to be restored"
     * and database schema update is required to apply this changes to a database
     * and update Doctrine's metadata and proxies.
     */
    const STATE_RESTORE = 'Restored';

    /**
     * An entity or field properties and database schema, Doctrine's metadata and proxies are up-to-date.
     */
    const STATE_ACTIVE  = 'Active';

    /**
     * @deprecated since 1.4. Will be removed in 2.0. Use STATE_UPDATE instead.
     */
    const STATE_UPDATED = 'Requires update';

    /**
     * @deprecated since 1.4. Will be removed in 2.0. Use STATE_DELETE instead.
     */
    const STATE_DELETED = 'Deleted';

    /**
     * The system entities and fields means that they cannot be removed by an administrator
     * and a developer who created them should take care about UI for them.
     */
    const OWNER_SYSTEM = 'System';

    /**
     * The system is fully responsible how the custom entities and fields are used on UI.
     */
    const OWNER_CUSTOM = 'Custom';

    /**
     * Changes are made with UI.
     */
    const ORIGIN_CUSTOM = 'Custom';

    /**
     * Changes are made on System level.
     */
    const ORIGIN_SYSTEM = 'System';
}
