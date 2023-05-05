<?php

namespace Oro\Bundle\ApiBundle\Form;

/**
 * Represents a service that is used to checks whether API form extension is activated.
 */
interface FormExtensionCheckerInterface
{
    /**
     * Checks whether API form extension is activated.
     */
    public function isApiFormExtensionActivated(): bool;
}
