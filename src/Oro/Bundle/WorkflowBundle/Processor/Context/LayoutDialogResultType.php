<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

class LayoutDialogResultType implements LayoutResultTypeInterface
{
    const NAME = 'layout_dialog';

    /** @var string */
    private $formRouteName;

    /**
     * @param string $formRouteName
     */
    public function __construct(string $formRouteName)
    {
        $this->formRouteName = $formRouteName;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return bool
     */
    public function supportsCustomForm(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getFormRouteName(): string
    {
        return $this->formRouteName;
    }
}
