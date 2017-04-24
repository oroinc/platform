<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

interface TransitActionResultTypeInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return bool
     */
    public function supportsCustomForm(): bool;
}
