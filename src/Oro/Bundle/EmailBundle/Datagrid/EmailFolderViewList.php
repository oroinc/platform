<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\DataGridBundle\Extension\GridViews\AbstractViewsList;
use Oro\Bundle\EmailBundle\Model\FolderType;

class EmailFolderViewList extends AbstractViewsList
{
    /**
     * {@inheritDoc}
     */
    protected function getViewsList()
    {
        return [
            new View(
                'oro.email.datagrid.emailfolder.view.inbox',
                [
                    'folders' => ['value' => FolderType::INBOX]
                ]
            ),
            new View(
                'oro.email.datagrid.emailfolder.view.sent',
                [
                    'folders' => ['value' => FolderType::SENT]
                ]
            ),
        ];
    }
}
