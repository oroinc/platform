<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

/**
 * Defines the contract for layout-based workflow transition action result types.
 *
 * Extends the base result type interface to add layout-specific functionality, particularly
 * the ability to specify a form route name for rendering transition forms within layout contexts.
 * Implementations represent layout-based result types such as layout dialogs or layout pages.
 */
interface LayoutResultTypeInterface extends TransitActionResultTypeInterface
{
    public function getFormRouteName(): string;
}
