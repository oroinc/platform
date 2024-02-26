<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tbl_organization')]
class TestOrganization
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var Collection<int, TestBusinessUnit>
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: TestBusinessUnit::class)]
    protected ?Collection $businessUnits = null;

    /**
     * @var Collection<int, TestUser>
     */
    #[ORM\ManyToMany(targetEntity: TestUser::class, mappedBy: 'organizations')]
    protected ?Collection $users = null;
}
