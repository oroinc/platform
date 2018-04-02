<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class Select2ChoiceType extends Select2Type
{
    public function __construct()
    {
        parent::__construct(ChoiceType::class, 'oro_select2_choice');
    }
}
