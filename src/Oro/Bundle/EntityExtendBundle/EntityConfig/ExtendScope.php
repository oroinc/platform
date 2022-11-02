<?php

namespace Oro\Bundle\EntityExtendBundle\EntityConfig;

/**
 * Provides constants that represent a state of extended entities and fields.
 */
class ExtendScope
{
    /**
     * New entity or field is registered in entity config and database schema update is required
     * to apply changes to a database and update Doctrine's metadata and proxies.
     */
    public const STATE_NEW = 'New';

    /**
     * An entity or field properties were modified and database schema update is required
     * to apply this changes to a database and update Doctrine's metadata and proxies.
     */
    public const STATE_UPDATE = 'Requires update';

    /**
     * An entity or field was marked as "to be deleted" and database schema update is required
     * to apply this changes to a database and update Doctrine's metadata and proxies.
     *
     * After database schema is updated the deleted entity or field is still available and can be
     * restored if it will be needed. To check if an entity or field is deleted use extend.is_deleted
     */
    public const STATE_DELETE = 'Deleted';

    /**
     * The previously deleted entity (not implemented yet) or field was marked as "to be restored"
     * and database schema update is required to apply this changes to a database
     * and update Doctrine's metadata and proxies.
     */
    public const STATE_RESTORE = 'Restored';

    /**
     * An entity or field properties and database schema, Doctrine's metadata and proxies are up-to-date.
     */
    public const STATE_ACTIVE  = 'Active';

    /**
     * The system entities and fields means that they cannot be removed by an administrator
     * and a developer who created them should take care about UI for them.
     */
    public const OWNER_SYSTEM = 'System';

    /**
     * The system is fully responsible how the custom entities and fields are used on UI.
     */
    public const OWNER_CUSTOM = 'Custom';
}
