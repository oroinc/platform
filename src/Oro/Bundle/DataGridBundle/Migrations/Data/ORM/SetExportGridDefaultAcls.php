<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Data\ORM;

use Oro\Bundle\SecurityBundle\Migrations\Data\ORM\AbstractLoadAclData;

/**
 * Class sets Grid Export permission to true by default for all Roles
 */
class SetExportGridDefaultAcls extends AbstractLoadAclData
{
    #[\Override]
    protected function getDataPath()
    {
        return '';
    }

    #[\Override]
    protected function getAclData()
    {
        return [
            self::ALL_ROLES => [
                'permissions' => ['action|oro_datagrid_gridview_export' => ['EXECUTE']]
            ]
        ];
    }
}
