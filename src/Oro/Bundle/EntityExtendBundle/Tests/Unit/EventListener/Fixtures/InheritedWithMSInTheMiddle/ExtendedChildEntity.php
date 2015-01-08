<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Fixtures\InheritedWithMSInTheMiddle;

use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * @MappedSuperclass()
 */
abstract class ExtendedChildEntity extends BaseEntity
{
}
