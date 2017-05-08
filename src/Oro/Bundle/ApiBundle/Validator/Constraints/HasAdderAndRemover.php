<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class HasAdderAndRemover extends Constraint implements ConstraintWithStatusCodeInterface
{
    public $message = 'oro.api.form.no_adder_and_remover';
    public $severalPairsMessage = 'oro.api.form.no_adder_and_remover_multiple';

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
