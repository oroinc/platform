<?php

namespace Oro\Bundle\DataGridBundle\Datagrid\Common;

use Oro\Bundle\DataGridBundle\Common\Object;
use Oro\Bundle\DataGridBundle\Datagrid\Builder;

class DatagridConfiguration extends Object
{
    const DATASOURCE_PATH = '[source]';
    const DATASOURCE_TYPE_PATH = '[source][type]';
    const BASE_DATAGRID_CLASS_PATH  = '[options][base_datagrid_class]';

    // Use this option as workaround for http://www.doctrine-project.org/jira/browse/DDC-2794
    const DATASOURCE_SKIP_COUNT_WALKER_PATH = '[options][skip_count_walker]';

    /**
     * This option refers to ACL resource that will be checked before datagrid is loaded.
     */
    const ACL_RESOURCE_PATH = '[acl_resource]';

    /**
     * This option makes possible to skip apply of ACL adjustment to source query of datagrid.
     */
    const DATASOURCE_SKIP_ACL_APPLY_PATH = '[source][skip_acl_apply]';

    /**
     * @return string
     */
    public function getDatasourceType()
    {
        return $this->offsetGetByPath(self::DATASOURCE_TYPE_PATH);
    }

    /**
     * Get value of "acl_resource" option from datagrid configuration.
     *
     * @return string|null
     */
    public function getAclResource()
    {
        $result = $this->offsetGetByPath(self::ACL_RESOURCE_PATH);

        if (!$result) {
            // Support backward compatibility until 1.11 to get this option from deprecated path.
            $result = $this->offsetGetByPath(Builder::DATASOURCE_ACL_PATH);
        }

        return $result;
    }

    /**
     * Check if ACL apply to source query of datagrid should be skipped
     *
     * @return bool
     */
    public function isDatasourceSkipAclApply()
    {
        $result = $this->offsetGetByPath(self::DATASOURCE_SKIP_ACL_APPLY_PATH, false);

        if (!$result) {
            // Support backward compatibility until 1.11 to get this option from deprecated path.
            $result = $this->offsetGetByPath(Builder::DATASOURCE_SKIP_ACL_CHECK, false);
        }

        return (bool)$result;
    }
}
