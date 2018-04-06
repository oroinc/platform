<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\GridViews\AbstractViewsList;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;

class EmailTemplatesViewList extends AbstractViewsList
{
    /**
     * {@inheritDoc}
     */
    protected function getViewsList()
    {
        return [
            new View(
                $this->translator->trans('oro.email.datagrid.emailtemplate.view.all_templates')
            ),
            new View(
                $this->translator->trans('oro.email.datagrid.emailtemplate.view.system_templates'),
                [
                    'isSystem' => [
                        'value' => 1
                    ]
                ]
            )
        ];
    }
}
