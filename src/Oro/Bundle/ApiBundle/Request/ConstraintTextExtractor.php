<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator;

class ConstraintTextExtractor implements ConstraintTextExtractorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConstraintStatusCode(Validator\Constraint $constraint)
    {
        return Response::HTTP_BAD_REQUEST;
    }

    /**
     * {@inheritdoc}
     */
    public function getConstraintCode(Validator\Constraint $constraint)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getConstraintType(Validator\Constraint $constraint)
    {
        return ValueNormalizerUtil::humanizeClassName(get_class($constraint), 'Constraint');
    }
}
