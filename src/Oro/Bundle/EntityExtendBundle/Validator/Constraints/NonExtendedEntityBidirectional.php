<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating bidirectional relations with non-extended entities.
 *
 * This constraint ensures that bidirectional relations can only be created when the target
 * entity is also extended. Additionally, it validates that one-to-many relations must be
 * bidirectional, preventing unidirectional one-to-many relations which could lead to
 * inconsistent relationship management.
 */
class NonExtendedEntityBidirectional extends Constraint
{
    public const VALIDATOR_ALIAS = 'oro_entity_extend.validator.non_extended_entity_bidirectional';

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
