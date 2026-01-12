<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint that validates a collection contains at least one primary item.
 *
 * This constraint ensures that collections implementing the PrimaryItem interface
 * have at least one item marked as primary, preventing invalid states where no
 * primary item is designated.
 */
class ContainsPrimary extends Constraint
{
    public $message = 'One of the items must be set as primary.';
}
