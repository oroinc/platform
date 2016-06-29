<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="company_table")
 */
class Company
{
    /**
     * @ORM\Column(name="name", type="string", length=50)
     * @ORM\Id
     */
    protected $name;

    /**
     * @ORM\ManyToMany(targetEntity="Group")
     * @ORM\JoinTable(name="company_to_group_table",
     *      joinColumns={@ORM\JoinColumn(name="company_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="company_group_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $groups;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->groups = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return Group[]|Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param Group $group
     */
    public function addGroup(Group $group)
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
        }
    }

    /**
     * @param Group $group
     */
    public function removeGroup(Group $group)
    {
        if ($this->groups->contains($group)) {
            $this->groups->removeElement($group);
        }
    }
}
