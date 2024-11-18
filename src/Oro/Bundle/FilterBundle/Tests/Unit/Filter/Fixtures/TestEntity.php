<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

#[ORM\Entity]
class TestEntity
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\ManyToMany(targetEntity: TestEnumValue::class)]
    protected $values;

    /** @var string */
    protected $valuesSnapshot;

    public function getValuesSnapshot()
    {
        return $this->valuesSnapshot;
    }

    public function setValuesSnapshot($value)
    {
        $this->valuesSnapshot = $value;

        return $this;
    }
}
