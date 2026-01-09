<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

/**
 * Represents a layout dialog result type for workflow transition actions.
 *
 * This result type indicates that the transition action result should be rendered as a dialog within
 * a layout context. It supports custom form handling and requires a form route name to be specified
 * for rendering the transition form within the dialog.
 */
class LayoutDialogResultType implements LayoutResultTypeInterface
{
    public const NAME = 'layout_dialog';

    /** @var string */
    private $formRouteName;

    public function __construct(string $formRouteName)
    {
        $this->formRouteName = $formRouteName;
    }

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

    #[\Override]
    public function getFormRouteName(): string
    {
        return $this->formRouteName;
    }
}
