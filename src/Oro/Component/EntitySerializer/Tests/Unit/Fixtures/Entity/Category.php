<?php

namespace Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'category_table')]
class Category
{
    #[ORM\Column(name: 'name', type: Types::STRING, length: 50)]
    #[ORM\Id]
    private ?string $name;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 255, unique: true)]
    private ?string $label;

    public function __construct(string $name = null, string $label = null)
    {
        $this->name = $name;
        $this->label = $label;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }
}
