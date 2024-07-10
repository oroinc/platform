<?php

namespace Oro\Bundle\FormBundle\Captcha;

/**
 * Registry to hold a list of CAPTCHA protected forms.
 */
class CaptchaProtectedFormsRegistry
{
    private array $protectedForms = [];

    public function __construct(iterable $protectedForms)
    {
        if ($protectedForms instanceof \Traversable) {
            $protectedForms = iterator_to_array($protectedForms);
        }

        $this->protectedForms = array_keys($protectedForms);
    }

    public function protectForm(string $name): void
    {
        $this->protectedForms[] = $name;
    }

    public function getProtectedForms(): array
    {
        return $this->protectedForms;
    }
}
