<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Stubs;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Model\GridQueryDesignerInterface;

class GridAwareQueryDesignerStub extends AbstractQueryDesigner implements GridQueryDesignerInterface
{
    public const GRID_PREFIX = 'test_grid_';

    /** @var string */
    private $entity;

    /** @var string */
    private $definition;

    public function __construct(?string $entity = null, ?string $definition = null)
    {
        $this->entity = $entity;
        $this->definition = $definition;
    }

    #[\Override]
    public function getEntity()
    {
        return $this->entity;
    }

    #[\Override]
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    #[\Override]
    public function getDefinition()
    {
        return $this->definition;
    }

    #[\Override]
    public function setDefinition($definition)
    {
        $this->definition = $definition;
    }

    #[\Override]
    public function getGridPrefix(): string
    {
        return self::GRID_PREFIX;
    }
}
