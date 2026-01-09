<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

/**
 * Defines the contract for workflow transition action result types.
 *
 * Result types determine how the result of a workflow transition action is rendered or processed,
 * such as template responses, layout dialogs, or layout pages. Implementations of this interface
 * specify the name of the result type and whether it supports custom form handling.
 */
interface TransitActionResultTypeInterface
{
    public function getName(): string;

    public function supportsCustomForm(): bool;
}
