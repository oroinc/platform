<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint that validates a related entity reference exists.
 *
 * This constraint ensures that a related entity specified by ID and class actually
 * exists in the database, preventing references to non-existent entities in API
 * requests and form submissions.
 */
class RelatedEntity extends Constraint
{
    public $message = 'The entity was not found.';

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_form.related_entity_validator';
    }
}
