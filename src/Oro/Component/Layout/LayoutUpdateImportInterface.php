<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Model\LayoutUpdateImport;

interface LayoutUpdateImportInterface
{
    /**
     * @return LayoutUpdateImport
     */
    public function getImport();

    /**
     * @param LayoutUpdateImport $import
     */
    public function setImport(LayoutUpdateImport $import);

    /**
     * @param ImportsAwareLayoutUpdateInterface $parentLayoutUpdate
     */
    public function setParentUpdate(ImportsAwareLayoutUpdateInterface $parentLayoutUpdate);
}
