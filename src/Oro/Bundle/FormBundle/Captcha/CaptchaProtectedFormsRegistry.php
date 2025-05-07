<?php

namespace Oro\Bundle\FormBundle\Captcha;

/**
 * The registry to hold a list of CAPTCHA protected forms.
 */
class CaptchaProtectedFormsRegistry
{
    public function __construct(
        private array $protectedForms
    ) {
    }

    public function protectForm(string $name): void
    {
        $this->protectedForms[] = $name;
    }

    /**
     * @return string[]
     */
    public function getProtectedForms(): array
    {
        return $this->protectedForms;
    }
}
