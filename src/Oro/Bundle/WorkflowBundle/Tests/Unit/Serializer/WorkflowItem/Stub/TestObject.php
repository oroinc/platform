<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\WorkflowItem\Stub;

class TestObject
{
    private ?string $code;

    public function __construct(string $code = null)
    {
        $this->code = $code;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }
}
