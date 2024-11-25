<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

use Oro\Bundle\WorkflowBundle\Event\WorkflowItemAwareEvent;

/**
 * Workflow event that is triggered during transition form build.
 */
final class AttributeFormInitEvent extends WorkflowItemAwareEvent
{
    #[\Override]
    public function getName(): string
    {
        return 'attribute_form_init';
    }
}
