<?php

namespace Oro\Bundle\FormBundle\Form\Extension\JsValidation;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Interface for constraints provider.
 */
interface ConstraintsProviderInterface
{
    /**
     * Gets constraints that should be checked on form view
     *
     * @param FormInterface $form
     * @return Constraint[]
     */
    public function getFormConstraints(FormInterface $form);
}
