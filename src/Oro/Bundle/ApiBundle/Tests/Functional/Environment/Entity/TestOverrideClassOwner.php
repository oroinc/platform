<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\ExtendTestOverrideClassOwner;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(name="test_api_override_owner")
 * @ORM\Entity
 * @Config
 */
class TestOverrideClassOwner extends ExtendTestOverrideClassOwner implements TestFrameworkEntityInterface
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
     * @var TestOverrideClassTarget|null
     *
     * @ORM\ManyToOne(targetEntity="TestOverrideClassTarget")
     * @ORM\JoinColumn(name="target_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $target;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="TestOverrideClassTarget", inversedBy="owners")
     * @ORM\JoinTable(name="test_api_override_rel_targets",
     *     joinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="target_id", referencedColumnName="id")}
     * )
     */
    private $targets;

    /**
     * @var TestOverrideClassTargetA|null
     *
     * @ORM\ManyToOne(targetEntity="TestOverrideClassTargetA")
     * @ORM\JoinColumn(name="another_target_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $anotherTarget;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="TestOverrideClassTargetA", inversedBy="owners")
     * @ORM\JoinTable(name="test_api_override_a_rel_ts",
     *     joinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="target_id", referencedColumnName="id")}
     * )
     */
    private $anotherTargets;

    public function __construct()
    {
        parent::__construct();

        $this->targets = new ArrayCollection();
        $this->anotherTargets = new ArrayCollection();
    }

    /**
     * @return TestOverrideClassTarget|null
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @param TestOverrideClassTarget|null $target
     */
    public function setTarget(TestOverrideClassTarget $target = null)
    {
        $this->target = $target;
    }

    /**
     * @return Collection|TestOverrideClassTarget[]
     */
    public function getTargets()
    {
        return $this->targets;
    }

    /**
     * @param TestOverrideClassTarget $target
     */
    public function addTarget(TestOverrideClassTarget $target)
    {
        if (!$this->targets->contains($target)) {
            $this->targets->add($target);
            $target->addOwner($this);
        }
    }

    /**
     * @param TestOverrideClassTarget $target
     */
    public function removeTarget(TestOverrideClassTarget $target)
    {
        if ($this->targets->contains($target)) {
            $this->targets->removeElement($target);
            $target->removeOwner($this);
        }
    }

    /**
     * @return TestOverrideClassTargetA|null
     */
    public function getAnotherTarget()
    {
        return $this->anotherTarget;
    }

    /**
     * @param TestOverrideClassTargetA|null $target
     */
    public function setAnotherTarget(TestOverrideClassTargetA $target = null)
    {
        $this->anotherTarget = $target;
    }

    /**
     * @return Collection|TestOverrideClassTargetA[]
     */
    public function getAnotherTargets()
    {
        return $this->anotherTargets;
    }

    /**
     * @param TestOverrideClassTargetA $target
     */
    public function addAnotherTarget(TestOverrideClassTargetA $target)
    {
        if (!$this->anotherTargets->contains($target)) {
            $this->anotherTargets->add($target);
            $target->addOwner($this);
        }
    }

    /**
     * @param TestOverrideClassTargetA $target
     */
    public function removeAnotherTarget(TestOverrideClassTargetA $target)
    {
        if ($this->anotherTargets->contains($target)) {
            $this->anotherTargets->removeElement($target);
            $target->removeOwner($this);
        }
    }
}
