<?php
namespace Oro\Bundle\TagBundle\Grid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

/**
 * @TODO Needs refactoring
 */
class GridTaskPropertyFormatter
{
    function getValue(ResultRecordInterface $record) {
        return $record->getValue('tags');
    }
}
