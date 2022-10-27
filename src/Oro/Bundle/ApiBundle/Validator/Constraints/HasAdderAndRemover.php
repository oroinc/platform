<?php

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether an to-many association has methods to add and to remove elements.
 *
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
    public function getStatusCode(): int
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
