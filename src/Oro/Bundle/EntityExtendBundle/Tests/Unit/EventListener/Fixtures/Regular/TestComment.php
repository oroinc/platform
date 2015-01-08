<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Fixtures\Regular;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class TestComment
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
}
