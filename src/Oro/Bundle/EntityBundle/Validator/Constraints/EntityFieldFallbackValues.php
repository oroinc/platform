<?php

namespace Oro\Bundle\EntityBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class EntityFieldFallbackValues extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.entity.entity_field_fallback_value.invalid';

    /**
     * @var string
     */
    public $route;

    public function __construct($options = null)
    {
        parent::__construct($options);

        $this->route = $options['route'] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return EntityFieldFallbackValuesValidator::ALIAS;
    }

}
