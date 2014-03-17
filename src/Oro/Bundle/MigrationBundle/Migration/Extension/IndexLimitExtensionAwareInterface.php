<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

/**
 * IndexLimitExtensionAwareInterface should be implemented
 * by migrations that depends on a IndexLimitExtension.
 */
interface IndexLimitExtensionAwareInterface
{
    /**
     * Sets the IndexLimitExtension
     *
     * @param IndexLimitExtension $indexLimitExtension
     */
    public function setIndexLimitExtension(IndexLimitExtension $indexLimitExtension);
}
