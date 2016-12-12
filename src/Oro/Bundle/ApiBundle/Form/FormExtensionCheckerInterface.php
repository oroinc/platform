<?php

namespace Oro\Bundle\ApiBundle\Form;

interface FormExtensionCheckerInterface
{
    /**
     * Checks whether Data API form extension is activated.
     */
    public function isApiFormExtensionActivated();
}
