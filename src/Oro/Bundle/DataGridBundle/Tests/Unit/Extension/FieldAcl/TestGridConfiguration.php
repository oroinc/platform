<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\FieldAcl;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class TestGridConfiguration extends DatagridConfiguration
{
    public function __construct($params)
    {
        parent::__construct($params);
    }
}
