<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

class LayoutPageResultType implements LayoutResultTypeInterface
{
    const NAME = 'layout_page';

    /** @var string */
    private $formRouteName;

    public function __construct(string $formRouteName)
    {
        $this->formRouteName = $formRouteName;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function supportsCustomForm(): bool
    {
        return true;
    }

    public function getFormRouteName(): string
    {
        return $this->formRouteName;
    }
}
