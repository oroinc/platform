<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Fixtures\InheritedWithValues;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityExtendBundle\Attribute\ORM\DiscriminatorValue;

#[ORM\Entity]
#[DiscriminatorValue('child')]
class ChildEntity extends BaseEntity
{
}
