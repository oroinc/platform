<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Select2-enhanced entity selection form type.
 *
 * This type wraps Symfony's {@see EntityType} with Select2 JavaScript functionality,
 * providing an enhanced user interface for selecting entities from the database
 * with search and filtering capabilities.
 */
class Select2EntityType extends Select2Type
{
    public function __construct()
    {
        parent::__construct(EntityType::class, 'oro_select2_entity');
    }
}
