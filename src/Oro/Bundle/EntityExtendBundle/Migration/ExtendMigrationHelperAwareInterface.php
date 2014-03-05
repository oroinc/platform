<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

/**
 * ExtendMigrationHelperAwareInterface should be implemented by migrations that depends on a ExtendMigrationHelper.
 */
interface ExtendMigrationHelperAwareInterface
{
    /**
     * Sets the ExtendMigrationHelper
     *
     * @param ExtendMigrationHelper $extendMigrationHelper
     */
    public function setExtendSchemaHelper(ExtendMigrationHelper $extendMigrationHelper);
}
