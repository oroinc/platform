<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Stubs;

use Oro\Bundle\QueryDesignerBundle\Grid\Extension\GroupingOrmFilterDatasourceAdapter as BaseAdapter;

class GroupingOrmFilterDatasourceAdapter extends BaseAdapter
{
    /**
     * Variable used for generating predictable parameter names
     *
     * @var int
     */
    public static $paramCount = 0;

    /**
     * {@inheritdoc}
     */
    public function generateParameterName($filterName)
    {
        return preg_replace('#[^a-z0-9]#i', '', $filterName) . self::$paramCount++;
    }
}
