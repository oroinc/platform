<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model;

/**
 * This model is used to test subresources to a model that is not registered in API.
 */
class TestUnregisteredModel
{
    /** @var int|null */
    private $id;

    /** @var string|null */
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
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
