<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

interface LayoutResultTypeInterface extends TransitActionResultTypeInterface
{
    public function getFormRouteName(): string;
}
