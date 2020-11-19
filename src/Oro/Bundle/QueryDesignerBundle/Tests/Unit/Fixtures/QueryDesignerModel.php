<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Model\GridQueryDesignerInterface;

class QueryDesignerModel extends AbstractQueryDesigner implements GridQueryDesignerInterface
{
    public const GRID_PREFIX = 'test_grid_';

    /** @var string */
    private $entity;

    /** @var string */
    private $definition;

    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }

    public function getGridPrefix()
    {
        return self::GRID_PREFIX;
    }
}
