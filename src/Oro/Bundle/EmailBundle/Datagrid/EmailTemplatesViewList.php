<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\DataGridBundle\Extension\GridViews\AbstractViewsList;

class EmailTemplatesViewList extends AbstractViewsList
{
    /**
     * {@inheritDoc}
     */
    protected function getViewsList()
    {
        return [
            new View(
                'oro.email.datagrid.emailtemplate.view.all_templates'
            ),
            new View(
                'oro.email.datagrid.emailtemplate.view.system_templates',
                [
                    'isSystem' => [
                        'value' => 1
                    ]
                ]
            )
        ];
    }
}
