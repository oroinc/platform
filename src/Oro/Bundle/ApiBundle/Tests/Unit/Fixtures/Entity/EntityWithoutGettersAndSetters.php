<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="entity_without_getters_and_setters_table")
 */
class EntityWithoutGettersAndSetters
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50)
     */
    public $name;

    /**
     * @var Category|null
     *
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="category_name", referencedColumnName="name", nullable=false)
     **/
    public $category;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="Group")
     * @ORM\JoinTable(name="user_to_group_table",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_group_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    public $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }
}
