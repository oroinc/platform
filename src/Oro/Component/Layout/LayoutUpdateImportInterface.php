<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Model\LayoutUpdateImport;

/**
 * Defines the contract for layout updates that manage imports of other layout updates.
 *
 * Implementations of this interface handle the import relationship between layout updates,
 * storing import metadata and maintaining the parent-child relationship in the import hierarchy.
 */
interface LayoutUpdateImportInterface
{
    /**
     * @return LayoutUpdateImport
     */
    public function getImport();

    public function setImport(LayoutUpdateImport $import);

    public function setParentUpdate(ImportsAwareLayoutUpdateInterface $parentLayoutUpdate);
}
