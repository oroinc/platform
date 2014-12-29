<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider\Fixtures\Hierarchy;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table()
 */
class TestEntity3
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;
}
