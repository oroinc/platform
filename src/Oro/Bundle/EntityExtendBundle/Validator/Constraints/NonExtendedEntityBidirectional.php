<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class NonExtendedEntityBidirectional extends Constraint
{
    const VALIDATOR_ALIAS = 'oro_entity_extend.validator.non_extended_entity_bidirectional';

    /**
     * @var string
     */
    public $message = 'The field can\'t be set to \'Yes\' when target entity isn\'t extended';

    /**
     * @var string
     */
    public $unidirectionalNotAllowedMessage = 'The field can\'t be set to \'No\' when relation type is \'oneToMany\'';

    #[\Override]
    public function getTargets(): string|array
    {
        return static::CLASS_CONSTRAINT;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return static::VALIDATOR_ALIAS;
    }
}
