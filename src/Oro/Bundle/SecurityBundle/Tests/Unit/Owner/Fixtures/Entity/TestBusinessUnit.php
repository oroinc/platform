<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tbl_business_unit')]
class TestBusinessUnit
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TestBusinessUnit::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    protected ?TestBusinessUnit $owner = null;

    /**
     * @var Collection<int, TestUser>
     */
    #[ORM\ManyToMany(targetEntity: TestUser::class, mappedBy: 'businessUnits')]
    protected ?Collection $users = null;

    #[ORM\ManyToOne(targetEntity: TestOrganization::class, inversedBy: 'businessUnits')]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id')]
    protected ?TestOrganization $organization = null;
}
