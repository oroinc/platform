<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Tag
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 50)]
    protected ?string $name = null;

    /**
     * @var Collection<int, Tagging>
     */
    #[ORM\OneToMany(mappedBy: 'tag', targetEntity: Tagging::class)]
    protected ?Collection $tagging = null;
}
