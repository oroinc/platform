<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;

/**
 * @method GridRow getRowByNumber($rowNumber) @see Table::getRowByNumber($rowNumber)
 * @method GridRow[] getRows() @see Table::getRows()
 */
class FrontendTableGrid extends Table
{
    const ERROR_NO_ROW = "Can't get %s row, because there are only %s rows in grid";
    const ERROR_NO_ROW_CONTENT = 'Grid has no record with "%s" content';

    public function getTotalRecordsCount()
    {
        $rows = $this->getRows();

        return count($rows);
    }
}
