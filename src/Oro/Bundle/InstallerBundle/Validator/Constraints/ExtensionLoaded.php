<?php

namespace Oro\Bundle\InstallerBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ExtensionLoaded extends Constraint
{
    public $message = 'Extension %extension% is not installed';
}
