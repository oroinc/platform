<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Form\NamedValidationConstraint;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\ApiBundle\Validator\Constraints\ConstraintWithStatusCodeInterface;
use Oro\Bundle\SecurityBundle\Validator\Constraints\FieldAccessGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator;

/**
 * The default implementation of extractor that retrieves information from a validation constraint object.
 */
class ConstraintTextExtractor implements ConstraintTextExtractorInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConstraintStatusCode(Validator\Constraint $constraint): ?int
    {
        if ($constraint instanceof ConstraintWithStatusCodeInterface) {
            return $constraint->getStatusCode();
        }
        if ($constraint instanceof FieldAccessGranted) {
            return Response::HTTP_FORBIDDEN;
        }

        return Response::HTTP_BAD_REQUEST;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstraintCode(Validator\Constraint $constraint): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstraintType(Validator\Constraint $constraint): ?string
    {
        $suffix = 'Constraint';
        if ($constraint instanceof NamedValidationConstraint) {
            $constraintType = $constraint->getConstraintType();
            $delimiter = strrpos($constraintType, '\\');
            if (false !== $delimiter) {
                $constraintType = substr($constraintType, $delimiter + 1);
            }
            $constraintType = preg_replace('/\W+/', ' ', $constraintType);
            if (str_contains($constraintType, ' ')) {
                $constraintType = str_replace('_', ' ', strtolower($constraintType));
                $suffix = ' ' . strtolower($suffix);
                if (!str_ends_with($constraintType, $suffix)) {
                    $constraintType .= $suffix;
                }
            } else {
                $constraintType = ValueNormalizerUtil::humanizeClassName($constraintType, $suffix);
            }
        } else {
            $constraintType = ValueNormalizerUtil::humanizeClassName(\get_class($constraint), $suffix);
        }

        return $constraintType;
    }
}
