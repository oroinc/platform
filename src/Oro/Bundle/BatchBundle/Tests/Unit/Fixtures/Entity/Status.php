<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Status
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    protected ?string $status;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'statuses')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected ?User $user = null;
}
