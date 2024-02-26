<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Owner\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tbl_ownership_entity')]
class TestOwnershipEntity
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'id1', type: Types::INTEGER)]
    protected ?int $id1 = null;

    #[ORM\Column(name: 'name', type: Types::STRING)]
    protected ?string $name = null;

    #[ORM\ManyToOne(targetEntity: TestBusinessUnit::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    protected ?TestBusinessUnit $owner = null;

    #[ORM\ManyToOne(targetEntity: TestOrganization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id')]
    protected ?TestOrganization $organization = null;
}
