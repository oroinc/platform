<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Model;

class SomeModel
{
    private ?int $id;
    private ?string $name = null;

    public function __construct(?int $id)
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
