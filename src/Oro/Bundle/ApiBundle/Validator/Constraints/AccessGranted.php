<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;

/**
 * This constraint can be used to check if an access to read an associated entity is granted.
 * By default the VIEW permission is used to check.
 *
 * @Annotation
 */
class AccessGranted extends Constraint implements ConstraintWithStatusCodeInterface
{
    public $message = 'oro.api.form.no_access';

    /** @var string */
    public $permission = 'VIEW';

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return Response::HTTP_FORBIDDEN;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_api.validator.access_granted';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
