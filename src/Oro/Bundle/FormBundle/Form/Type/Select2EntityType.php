<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class Select2EntityType extends Select2Type
{
    public function __construct()
    {
        parent::__construct(EntityType::class, 'oro_select2_entity');
    }
}
