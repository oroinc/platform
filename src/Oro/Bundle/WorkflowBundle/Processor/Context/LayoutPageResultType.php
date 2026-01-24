<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

/**
 * Represents a layout page result type for workflow transition actions.
 *
 * This result type indicates that the transition action result should be rendered as a full page within
 * a layout context. It supports custom form handling and requires a form route name to be specified
 * for rendering the transition form on the page.
 */
class LayoutPageResultType implements LayoutResultTypeInterface
{
    const NAME = 'layout_page';

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
