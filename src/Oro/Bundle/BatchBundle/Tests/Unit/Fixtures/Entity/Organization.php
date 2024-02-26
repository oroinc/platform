<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Organization
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    /**
     * @var Collection<int, BusinessUnit>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: BusinessUnit::class, cascade: ['ALL'])]
    protected ?Collection $businessUnits = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'organizations')]
    #[ORM\JoinTable(name: 'oro_user_organization')]
    protected ?Collection $users = null;
}
