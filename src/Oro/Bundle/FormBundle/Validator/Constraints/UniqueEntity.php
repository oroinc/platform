<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity as DoctrineUniqueEntityConstraint;

/**
 * The constraint for the {@see UniqueEntityValidator}.
 */
class UniqueEntity extends DoctrineUniqueEntityConstraint
{
    public $service = 'oro_form.validator_constraints.unique_entity';

    public ?bool $buildViolationAtEntityLevel = true;
}
