<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\ORM\Query\Stub;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Entity
{
    public function __construct($a = null, $b = null)
    {
        $this->a = $a;
        $this->b = $b;
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    public $a;

    /**
     * @ORM\Column(type="string")
     */
    public $b;
}
