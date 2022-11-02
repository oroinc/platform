<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Stub;

class TestTarget
{
    private ?int $id;

    public function __construct(int $id = null)
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
