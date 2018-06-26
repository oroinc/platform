<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(name="test_api_target",
 *      indexes={
 *          @ORM\Index(name="test_api_t_name_idx", columns={"name"})
 *     }
 * )
 * @ORM\Entity
 * @Config
 */
class TestTarget implements TestFrameworkEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    public $name;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="TestOwner", mappedBy="targets")
     * @ORM\JoinTable(name="test_api_rel_targets",
     *     joinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="target_id", referencedColumnName="id")}
     * )
     */
    protected $owners;

    public function __construct()
    {
        $this->owners = new ArrayCollection();
    }

    /**
     * @return Collection|TestOwner[]
     */
    public function getOwners()
    {
        return $this->owners;
    }

    /**
     * @param TestOwner $owner
     */
    public function addOwner(TestOwner $owner)
    {
        if (!$this->owners->contains($owner)) {
            $this->owners->add($owner);
            $owner->addTarget($this);
        }
    }

    /**
     * @param TestOwner $owner
     */
    public function removeOwner(TestOwner $owner)
    {
        if ($this->owners->contains($owner)) {
            $this->owners->removeElement($owner);
            $owner->removeTarget($this);
        }
    }
}
