<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

/**
 * This interface must be implemented by query designer classes that have own datagrid type.
 */
interface GridQueryDesignerInterface
{
    /**
     * Gets a prefix for the datagrid name.
     */
    public function getGridPrefix(): string;
}
