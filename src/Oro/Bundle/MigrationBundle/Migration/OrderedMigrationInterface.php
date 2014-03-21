<?php

namespace Oro\Bundle\MigrationBundle\Migration;

/**
 * Ordered Migration interface needs to be implemented by migrations,
 * which needs to have a specific order.
 * This works only for migrations located in the same directory
 * and allows to change migration's order within the same version.
 */
interface OrderedMigrationInterface
{
    /**
     * Get the order of this migration
     *
     * @return integer
     */
    public function getOrder();
}
