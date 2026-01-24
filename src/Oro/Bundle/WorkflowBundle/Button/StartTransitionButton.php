<?php

namespace Oro\Bundle\WorkflowBundle\Button;

/**
 * Represents a workflow start transition button for initiating new workflow instances.
 *
 * This button extends the base transition button functionality to provide template data
 * specific to starting new workflows on entities that do not yet have an active workflow item.
 */
class StartTransitionButton extends AbstractTransitionButton
{
}
