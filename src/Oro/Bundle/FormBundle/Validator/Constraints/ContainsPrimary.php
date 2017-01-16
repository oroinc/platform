<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ContainsPrimary extends Constraint
{
    public $message = 'One of the items must be set as primary.';
}
