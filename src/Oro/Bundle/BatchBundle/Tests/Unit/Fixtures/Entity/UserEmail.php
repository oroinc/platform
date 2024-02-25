<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class UserEmail
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'emails')]
    protected ?User $user = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected ?bool $primary = null;

    #[ORM\ManyToOne(targetEntity: EmailStatus::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'status_id', referencedColumnName: 'id')]
    protected ?EmailStatus $status = null;
}
