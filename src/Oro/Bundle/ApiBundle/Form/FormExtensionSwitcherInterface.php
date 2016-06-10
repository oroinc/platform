<?php

namespace Oro\Bundle\ApiBundle\Form;

interface FormExtensionSwitcherInterface
{
    /**
     * Switches to default form extension.
     */
    public function switchToDefaultFormExtension();

    /**
     * Switches to Data API form extension.
     */
    public function switchToApiFormExtension();
}
