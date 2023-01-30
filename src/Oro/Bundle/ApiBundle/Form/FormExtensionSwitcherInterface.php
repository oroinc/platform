<?php

namespace Oro\Bundle\ApiBundle\Form;

/**
 * Represents a service that is used to switch between the default and API form extensions.
 */
interface FormExtensionSwitcherInterface
{
    /**
     * Switches to default form extension.
     */
    public function switchToDefaultFormExtension(): void;

    /**
     * Switches to API form extension.
     */
    public function switchToApiFormExtension(): void;
}
