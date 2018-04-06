<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class Select2HiddenType extends Select2Type
{
    public function __construct()
    {
        parent::__construct(HiddenType::class, 'oro_select2_hidden');
    }
}
