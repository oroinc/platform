<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * Unlike TestOverrideClassTarget, this entity does not have a model that overrides it.
 */
#[ORM\Entity]
#[ORM\Table(name: 'test_api_override_a_target')]
#[ORM\Index(columns: ['name'], name: 'test_api_override_a_t_name_idx')]
#[Config]
class TestOverrideClassTargetA implements TestFrameworkEntityInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, nullable: true)]
    public ?string $name = null;

    /**
     * @var Collection<int, TestOverrideClassOwner>
     */
    #[ORM\ManyToMany(targetEntity: TestOverrideClassOwner::class, mappedBy: 'anotherTargets')]
    #[ORM\JoinTable(name: 'test_api_override_a_rel_ts')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'target_id', referencedColumnName: 'id')]
    protected ?Collection $owners = null;

    public function __construct()
    {
        $this->owners = new ArrayCollection();
    }

    /**
     * @return Collection|TestOverrideClassOwner[]
     */
    public function getOwners()
    {
        return $this->owners;
    }

    public function addOwner(TestOverrideClassOwner $owner)
    {
        if (!$this->owners->contains($owner)) {
            $this->owners->add($owner);
            $owner->addAnotherTarget($this);
        }
    }

    public function removeOwner(TestOverrideClassOwner $owner)
    {
        if ($this->owners->contains($owner)) {
            $this->owners->removeElement($owner);
            $owner->removeAnotherTarget($this);
        }
    }
}
