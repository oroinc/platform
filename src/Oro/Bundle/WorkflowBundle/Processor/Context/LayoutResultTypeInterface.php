<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

interface LayoutResultTypeInterface extends TransitActionResultTypeInterface
{
    /**
     * @return string
     */
    public function getFormRouteName(): string;
}
