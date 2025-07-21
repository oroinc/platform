<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Context;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;

class ResultTypeStub implements TransitActionResultTypeInterface
{
    private string $name;
    private bool $supportsCustom;

    public function __construct(string $name, bool $supportsCustom = false)
    {
        $this->name = $name;
        $this->supportsCustom = $supportsCustom;
    }

    #[\Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[\Override]
    public function supportsCustomForm(): bool
    {
        return $this->supportsCustom;
    }
}
