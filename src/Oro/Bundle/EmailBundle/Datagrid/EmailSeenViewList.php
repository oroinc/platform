<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\EmailBundle\Model\SeenType;

/**
 * Provides predefined grid views for email seen status filtering.
 *
 * Extends the email folder view list to add additional views for filtering emails
 * by their seen status, particularly for displaying newly received emails.
 */
class EmailSeenViewList extends EmailFolderViewList
{
    #[\Override]
    protected function getViewsList()
    {
        $parentArray = parent::getViewsList();

        return array_merge(
            $parentArray,
            [
                new View(
                    'oro.email.datagrid.filters.new',
                    [
                        'is_new' => ['value' => SeenType::NEWEST]
                    ]
                )
            ]
        );
    }
}
