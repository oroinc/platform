<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

interface TransitActionResultTypeInterface
{
    public function getName(): string;

    public function supportsCustomForm(): bool;
}
