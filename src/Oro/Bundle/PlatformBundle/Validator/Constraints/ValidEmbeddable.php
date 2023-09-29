<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Valid;

/**
 * Validation constraint that allows to apply Valid constraint with explicit validation groups specified in
 * "embeddedGroups" option.
 */
class ValidEmbeddable extends Valid
{
    public array $embeddedGroups = ['Default'];
}
