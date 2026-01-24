<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Select2-enhanced hidden field form type.
 *
 * This type wraps Symfony's {@see HiddenType} with Select2 JavaScript functionality,
 * allowing hidden fields to be enhanced with Select2 features for programmatic
 * value selection and manipulation.
 */
class Select2HiddenType extends Select2Type
{
    public function __construct()
    {
        parent::__construct(HiddenType::class, 'oro_select2_hidden');
    }
}
