<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Fixtures\InheritedWithMSInTheMiddle;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Oro\Bundle\EntityExtendBundle\Annotation\ORM\DiscriminatorValue;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * @ORM\Entity()
 * @DiscriminatorValue("child")
 * @MappedSuperclass()
 */
class ChildEntity extends BaseEntity implements ExtendEntityInterface
{
    use ExtendEntityTrait;
}
