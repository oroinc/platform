<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider\Fixtures\Hierarchy;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table]
class TestEntity1 extends ParentClass
{
}
