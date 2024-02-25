<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Group
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    /**
     * @var Collection<int, Item>
     */
    #[ORM\ManyToMany(targetEntity: Item::class)]
    protected ?Collection $items = null;
}
