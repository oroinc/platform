<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Fixtures\RegularWithAnnotation;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityExtendBundle\Annotation\ORM\DiscriminatorValue;

/**
 * @ORM\Entity()
 * @DiscriminatorValue("test")
 */
class TestComment
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="unassigned", type="boolean")
     */
    protected $unassigned;
}
