<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Length;

/**
 * Constraint for fieldName length validation.
 */
class FieldNameLength extends Length
{
    // By default should be created fields with name which length more than 1.
    public const MIN_LENGTH = 2;

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        if ($options === null) {
            $options = [
                'min' => self::MIN_LENGTH,
            ];
        }

        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return 'oro_entity_extend.validator.field_name_length';
    }
}
