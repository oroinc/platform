<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

#[ORM\Entity]
#[ORM\Table(name: 'test_api_override_owner')]
#[Config]
class TestOverrideClassOwner implements TestFrameworkEntityInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, nullable: true)]
    public ?string $name = null;

    #[ORM\ManyToOne(targetEntity: TestOverrideClassTarget::class)]
    #[ORM\JoinColumn(name: 'target_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?TestOverrideClassTarget $target = null;

    /**
     * @var Collection<int, TestOverrideClassTarget>
     */
    #[ORM\ManyToMany(targetEntity: TestOverrideClassTarget::class, inversedBy: 'owners')]
    #[ORM\JoinTable(name: 'test_api_override_rel_targets')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'target_id', referencedColumnName: 'id')]
    private ?Collection $targets = null;

    #[ORM\ManyToOne(targetEntity: TestOverrideClassTargetA::class)]
    #[ORM\JoinColumn(name: 'another_target_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?TestOverrideClassTargetA $anotherTarget = null;

    /**
     * @var Collection<int, TestOverrideClassTargetA>
     */
    #[ORM\ManyToMany(targetEntity: TestOverrideClassTargetA::class, inversedBy: 'owners')]
    #[ORM\JoinTable(name: 'test_api_override_a_rel_ts')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'target_id', referencedColumnName: 'id')]
    private ?Collection $anotherTargets = null;

    public function __construct()
    {
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

    public function setTarget(?TestOverrideClassTarget $target = null)
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

    public function addTarget(TestOverrideClassTarget $target)
    {
        if (!$this->targets->contains($target)) {
            $this->targets->add($target);
            $target->addOwner($this);
        }
    }

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

    public function setAnotherTarget(?TestOverrideClassTargetA $target = null)
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

    public function addAnotherTarget(TestOverrideClassTargetA $target)
    {
        if (!$this->anotherTargets->contains($target)) {
            $this->anotherTargets->add($target);
            $target->addOwner($this);
        }
    }

    public function removeAnotherTarget(TestOverrideClassTargetA $target)
    {
        if ($this->anotherTargets->contains($target)) {
            $this->anotherTargets->removeElement($target);
            $target->removeOwner($this);
        }
    }
}
