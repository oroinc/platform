<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;

class FrontendGrid extends Table implements GridMappedChildInterface
{
    const TABLE_ROW_ELEMENT = 'GridRow';
    const ERROR_NO_ROW = "Can't get %s row, because there are only %s rows in grid";
    const ERROR_NO_ROW_CONTENT = 'Grid has no record with "%s" content';

    /**
     * @return int
     */
    public function getTotalRecordsCount()
    {
        $rows = $this->getRows();

        return count($rows);
    }

    /**
     * {@inheritdoc}
     */
    public function getMappedChildElementName($name)
    {
        return $name;
    }
}
