<?php

namespace Oro\Component\Duplicator\Tests\Unit\Stub;

class EntityItem2
{
    /**
     * @var Entity3
     */
    protected $childEntity;

    /**
     * @return Entity3
     */
    public function getChildEntity()
    {
        return $this->childEntity;
    }

    /**
     * @param Entity3 $childEntity
     */
    public function setChildEntity($childEntity)
    {
        $this->childEntity = $childEntity;
    }
}
