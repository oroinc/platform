<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

/**
 * Represents a template-based result type for workflow transition actions.
 *
 * This result type indicates that the transition action result should be rendered as a template response.
 * It supports custom form handling, allowing transitions to use custom form types for data collection
 * and processing before the template is rendered.
 */
class TemplateResultType implements TransitActionResultTypeInterface
{
    const NAME = 'template_response';

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function supportsCustomForm(): bool
    {
        return true;
    }
}
