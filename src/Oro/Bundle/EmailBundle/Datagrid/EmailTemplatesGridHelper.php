<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class EmailTemplatesGridHelper
{
    /**
     * Returns callback for configuration of grid/actions visibility per row
     *
     * @return callable
     */
    public function getActionConfigurationClosure()
    {
        return function (ResultRecordInterface $record) {
            if ($record->getValue('isSystem')) {
                return array('delete' => false);
            }
        };
    }

    /**
     * Returns email template type choice list
     *
     * @return array
     */
    public function getTypeChoices()
    {
        return [
            'html' => 'oro.email.datagrid.emailtemplate.filter.type.html',
            'txt'  => 'oro.email.datagrid.emailtemplate.filter.type.txt'
        ];
    }
}
