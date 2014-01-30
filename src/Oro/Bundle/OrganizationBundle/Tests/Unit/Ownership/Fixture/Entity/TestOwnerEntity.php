<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Ownership\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class TestOwnerEntity
{
    /**
     * @var int
     *
     * @ORM\Column
     * @ORM\Id
     */
    protected $id;

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
