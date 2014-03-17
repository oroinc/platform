<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Totals\TestEntity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity
 */
class Test
{
    public function __construct($a = null, $b = null)
    {
        $this->a = $a;
        $this->b = $b;
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $name;

    /**
     * @ORM\Column(type="integer")
     */
    public $won;

    /**
     * @ORM\Column(type="integer")
     */
    public $status;
}
