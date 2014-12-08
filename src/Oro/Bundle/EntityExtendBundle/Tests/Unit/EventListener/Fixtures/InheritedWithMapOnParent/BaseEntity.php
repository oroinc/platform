<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Fixtures\InheritedWithMapOnParent;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorMap({"base": "BaseEntity", "child": "ChildEntity"})
 */
class BaseEntity
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
}
