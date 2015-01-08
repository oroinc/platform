<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Fixtures\InheritedWithMapOnParent;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ChildEntity extends BaseEntity
{
}
