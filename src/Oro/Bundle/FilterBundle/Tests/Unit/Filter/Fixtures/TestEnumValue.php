<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;

#[ORM\Entity]
class TestEnumValue extends EnumOption
{
}
