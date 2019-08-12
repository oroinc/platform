<?php

namespace Oro\Bundle\ApiBundle\Form;

/**
 * The instance of this class is used to store a current state of Forms extension,
 * that can be provide default forms or API forms.
 */
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
     * Switches to API form extension.
     */
    public function switchToApiFormExtension()
    {
        $this->isApiFormExtensionActivated = true;
    }
}
