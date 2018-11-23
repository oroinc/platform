<?php

namespace Oro\Bundle\AddressBundle\Extension\JsValidation;

use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganization;
use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintsProvider;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;

/**
 * This decorator sets parent form name option for NameOrOrganization constraint if needed.
 */
class ConstraintsProviderDecorator extends ConstraintsProvider
{
    /**
     * @var ConstraintsProvider
     */
    private $constraintsProvider;

    /**
     * @param ConstraintsProvider $constraintsProvider
     */
    public function __construct(ConstraintsProvider $constraintsProvider)
    {
        $this->constraintsProvider = $constraintsProvider;
    }

    /**
     * Gets constraints that should be checked on form view
     *
     * @param FormInterface $form
     * @return Constraint[]
     */
    public function getFormConstraints(FormInterface $form)
    {
        $constraints = $this->constraintsProvider->getFormConstraints($form);

        if (!$form->getParent()) {
            return $constraints;
        }

        foreach ($constraints as $constraint) {
            if ($constraint instanceof NameOrOrganization) {
                $constraint->parentFormName = $form->getName();
            }
        }

        return $constraints;
    }
}
