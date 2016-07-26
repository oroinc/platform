<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\Model\LayoutUpdateImport;

interface LayoutUpdateImportInterface
{
    /**
     * @param LayoutUpdateImport $import
     */
    public function setImport(LayoutUpdateImport $import);
}
