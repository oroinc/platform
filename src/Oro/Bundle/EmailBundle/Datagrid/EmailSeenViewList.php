<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\EmailBundle\Model\SeenType;

class EmailSeenViewList extends EmailFolderViewList
{
    /**
     * {@inheritDoc}
     */
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
