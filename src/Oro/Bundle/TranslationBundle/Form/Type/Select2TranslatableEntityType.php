<?php

namespace Oro\Bundle\TranslationBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2Type;

/**
 * Select2 form type for selecting translatable entities.
 *
 * Provides a Select2-enhanced form type for choosing translatable entities in forms.
 * Wraps the TranslatableEntityType with Select2 functionality to offer improved user
 * experience with search and selection capabilities for translatable entity fields.
 */
class Select2TranslatableEntityType extends Select2Type
{
    public function __construct()
    {
        parent::__construct(TranslatableEntityType::class, 'oro_select2_translatable_entity');
    }
}
