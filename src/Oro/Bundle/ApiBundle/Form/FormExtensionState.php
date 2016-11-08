<?php

namespace Oro\Bundle\ApiBundle\Form;

class FormExtensionState implements FormExtensionCheckerInterface
{
    /** @var bool */
    private $isApiFormExtensionActivated = false;

    /**
     * {@inheritdoc}
     */
    public function isApiFormExtensionActivated()
    {
        return $this->isApiFormExtensionActivated;
    }

    /**
     * Switches to default form extension.
     */
    public function switchToDefaultFormExtension()
    {
        $this->isApiFormExtensionActivated = false;
    }

    /**
     * Switches to Data API form extension.
     */
    public function switchToApiFormExtension()
    {
        $this->isApiFormExtensionActivated = true;
    }
}
