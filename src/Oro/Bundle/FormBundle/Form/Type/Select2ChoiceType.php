<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Select2-enhanced choice form type.
 *
 * This type wraps Symfony's {@see ChoiceType} with Select2 JavaScript functionality,
 * providing an enhanced user interface for selecting from a list of choices with
 * search and filtering capabilities.
 */
class Select2ChoiceType extends Select2Type
{
    public function __construct()
    {
        parent::__construct(ChoiceType::class, 'oro_select2_choice');
    }
}
