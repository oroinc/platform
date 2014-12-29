<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider\Fixtures\Hierarchy;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
class BaseEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;
}
