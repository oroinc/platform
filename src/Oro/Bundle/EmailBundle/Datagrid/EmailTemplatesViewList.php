<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\GridViews\AbstractViewsList;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;

/**
 * Provides predefined grid views for email templates filtering.
 *
 * Defines views for filtering email templates by type, including views for all templates
 * and system-only templates to facilitate template management in the admin interface.
 */
class EmailTemplatesViewList extends AbstractViewsList
{
    #[\Override]
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
