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
#[ORM\Table(name: 'test_api_owner')]
#[Config]
class TestOwner implements TestFrameworkEntityInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, nullable: true)]
    public ?string $name = null;

    #[ORM\ManyToOne(targetEntity: TestTarget::class)]
    #[ORM\JoinColumn(name: 'target_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?TestTarget $target = null;

    /**
     * @var Collection<int, TestTarget>
     */
    #[ORM\ManyToMany(targetEntity: TestTarget::class, inversedBy: 'owners')]
    #[ORM\JoinTable(name: 'test_api_rel_targets')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'target_id', referencedColumnName: 'id')]
    private ?Collection $targets = null;

    public function __construct()
    {
        $this->targets = new ArrayCollection();
    }

    /**
     * @return TestTarget|null
     */
    public function getTarget()
    {
        return $this->target;
    }

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

    public function addTarget(TestTarget $target)
    {
        if (!$this->targets->contains($target)) {
            $this->targets->add($target);
            $target->addOwner($this);
        }
    }

    public function removeTarget(TestTarget $target)
    {
        if ($this->targets->contains($target)) {
            $this->targets->removeElement($target);
            $target->removeOwner($this);
        }
    }
}
