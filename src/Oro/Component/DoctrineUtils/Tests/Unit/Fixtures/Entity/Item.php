<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Item
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    protected ?string $name = null;

    /**
     * @var Collection<int, Person>
     */
    #[ORM\ManyToMany(targetEntity: Person::class)]
    protected ?Collection $persons = null;

    #[ORM\OneToOne(targetEntity: Person::class)]
    protected ?Person $owner = null;
}
