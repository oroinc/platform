<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

class LayoutPageResultType implements LayoutResultTypeInterface
{
    public const NAME = 'layout_page';

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
