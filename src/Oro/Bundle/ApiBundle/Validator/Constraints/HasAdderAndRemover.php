<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class HasAdderAndRemover extends Constraint implements ConstraintWithStatusCodeInterface
{
    public $message = 'The "{{ class }}" class should have both "{{ adder }}" and "{{ remover }}" methods.';
    public $severalPairsMessage = 'The "{{ class }}" class should have any of the following method pairs: %s.';

    public $class;
    public $property;

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return Response::HTTP_NOT_IMPLEMENTED;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return ['class', 'property'];
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
