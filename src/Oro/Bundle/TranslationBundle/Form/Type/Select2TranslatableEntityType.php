<?php

namespace Oro\Bundle\TranslationBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2Type;

class Select2TranslatableEntityType extends Select2Type
{
    public function __construct()
    {
        parent::__construct(TranslatableEntityType::class, 'oro_select2_translatable_entity');
    }
}
