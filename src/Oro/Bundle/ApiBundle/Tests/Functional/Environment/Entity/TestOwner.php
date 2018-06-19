<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\ExtendTestOwner;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(name="test_api_owner")
 * @ORM\Entity
 * @Config
 */
class TestOwner extends ExtendTestOwner implements TestFrameworkEntityInterface
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
     * @var TestTarget|null
     *
     * @ORM\ManyToOne(targetEntity="TestTarget")
     * @ORM\JoinColumn(name="target_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $target;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="TestTarget", inversedBy="owners")
     * @ORM\JoinTable(name="test_api_rel_targets",
     *     joinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="target_id", referencedColumnName="id")}
     * )
     */
    private $targets;

    public function __construct()
    {
        parent::__construct();

        $this->targets = new ArrayCollection();
    }

    /**
     * @return TestTarget|null
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param TestTarget|null $target
     */
    public function setTarget(TestTarget $target = null)
    {
        $this->target = $target;
    }

    /**
     * @return Collection|TestTarget[]
     */
    public function getTargets()
    {
        return $this->targets;
    }

    /**
     * @param TestTarget $target
     */
    public function addTarget(TestTarget $target)
    {
        if (!$this->targets->contains($target)) {
            $this->targets->add($target);
            $target->addOwner($this);
        }
    }

    /**
     * @param TestTarget $target
     */
    public function removeTarget(TestTarget $target)
    {
        if ($this->targets->contains($target)) {
            $this->targets->removeElement($target);
            $target->removeOwner($this);
        }
    }
}
