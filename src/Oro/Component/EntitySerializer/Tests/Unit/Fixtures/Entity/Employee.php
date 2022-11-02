<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Employee extends Person
{
    /**
     * @ORM\Column(name="position", type="string", length=255)
     */
    protected $position;

    /**
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param string $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }
}
