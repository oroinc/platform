<?php

namespace Oro\Bundle\DataGridBundle\Datagrid\Common;

use Oro\Component\Config\Common\ConfigObject;

class ResultsObject extends ConfigObject
{
    /**
     * Path to total records parameter
     */
    const TOTAL_RECORDS_PATH = '[options][totalRecords]';

    /**
     * Path tp results data
     */
    const DATA_PATH = '[data]';

    /**
     * Gets total records parameter from results object
     *
     * @return int
     */
    public function getTotalRecords()
    {
        return (int)$this->offsetGetByPath(self::TOTAL_RECORDS_PATH);
    }
    /**
     * Sets total records parameter to results object
     *
     * @param int $value
     * @return $this
     */
    public function setTotalRecords($value)
    {
        return $this->offsetSetByPath(self::TOTAL_RECORDS_PATH, (int)$value);
    }

    /**
     * Gets data rows from results object
     *
     * @return array
     */
    public function getData()
    {
        return (array)$this->offsetGetByPath(self::DATA_PATH, []);
    }

    /**
     * Gets data rows from results object
     *
     * @param array $rows
     * @return $this
     */
    public function setData(array $rows)
    {
        return $this->offsetSetByPath(self::DATA_PATH, $rows);
    }
}
