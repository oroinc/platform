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
    private ?string $position = null;

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): void
    {
        $this->position = $position;
    }
}
