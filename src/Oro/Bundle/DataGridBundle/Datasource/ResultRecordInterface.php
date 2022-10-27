<?php

namespace Oro\Bundle\DataGridBundle\Datasource;

use Oro\Bundle\DataGridBundle\Exception\LogicException;

/**
 * Interface represents datagrid result rows
 */
interface ResultRecordInterface
{
    /**
     * Get value of record property by name
     *
     * @param  string $name
     *
     * @return mixed
     * @throws LogicException When cannot get value
     */
    public function getValue($name);

    /**
     * Get root entity of current result record
     *
     * @return object|null
     */
    public function getRootEntity();

    /**
     * Set value of property by name
     *
     * @param string $name
     * @param mixed $value
     */
    public function setValue($name, $value);
}
