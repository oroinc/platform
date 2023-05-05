<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether an access to an associated entity is granted.
 * By default the VIEW permission is used to check.
 *
 * @Annotation
 */
class AccessGranted extends Constraint implements ConstraintWithStatusCodeInterface
{
    public string $message = 'oro.api.form.no_access';

    public string $permission = 'VIEW';

    /**
     * {@inheritdoc}
     */
    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
