<?php

namespace Oro\Bundle\DataGridBundle\Datasource;

/**
 * @deprecated Merge with ResultRecordInterface
 * @see \Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface
 */
interface ResultRecordWritableInterface
{
    /**
     * Set value of property by name
     *
     * @param string $name
     * @param mixed $value
     */
    public function setValue($name, $value);
}
