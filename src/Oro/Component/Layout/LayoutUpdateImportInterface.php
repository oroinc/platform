<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Model\LayoutUpdateImport;

interface LayoutUpdateImportInterface
{
    /**
     * @return LayoutUpdateImport
     */
    public function getImport();

    public function setImport(LayoutUpdateImport $import);

    public function setParentUpdate(ImportsAwareLayoutUpdateInterface $parentLayoutUpdate);
}
