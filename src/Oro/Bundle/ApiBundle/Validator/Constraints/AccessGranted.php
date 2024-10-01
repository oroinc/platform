<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether an access to an associated entity is granted.
 * By default the VIEW permission is used to check.
 *
 * @Annotation
 */
#[Attribute]
class AccessGranted extends Constraint implements ConstraintWithStatusCodeInterface
{
    public string $message = 'oro.api.form.no_access';

    public string $permission = 'VIEW';

    #[\Override]
    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
