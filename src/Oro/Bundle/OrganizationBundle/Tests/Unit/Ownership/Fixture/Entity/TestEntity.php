<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Ownership\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class TestEntity
{
    /**
     * @var int
     */
    #[ORM\Column]
    #[ORM\Id]
    protected $id;

    #[ORM\ManyToOne(targetEntity: TestOwnerEntity::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    protected ?TestOwnerEntity $owner = null;

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

    /**
     * @return TestOwnerEntity
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param TestOwnerEntity $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }
}
