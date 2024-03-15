<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model;

/**
 * This model is used to test subresources to a model that is not accessible via API.
 */
class TestUnaccessibleModel
{
    /** @var string|null */
    private $id;

    /** @var string|null */
    private $name;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
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
