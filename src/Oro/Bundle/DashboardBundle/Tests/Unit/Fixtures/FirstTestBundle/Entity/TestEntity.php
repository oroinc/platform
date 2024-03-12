<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\FirstTestBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'test')]
class TestEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public $id;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    public ?\DateTimeInterface $createdAt = null;
}
