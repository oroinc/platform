<?php

/*
 * This file is a copy of {@see Symfony\Component\Validator\Constraints\All}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Composite;

/**
 * When applied to an array (or Traversable object), this constraint allows you to apply
 * a collection of constraints to each element of the array.
 * The difference with Symfony constraint is that uninitialized PersistentCollection is not validated.
 * @see Symfony\Component\Validator\Constraints\All
 * @see Symfony\Component\Validator\Constraints\AllValidator
 *
 * @Annotation
 */
class All extends Composite
{
    /** @var array */
    public $constraints = [];

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'constraints';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return ['constraints'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCompositeOption()
    {
        return 'constraints';
    }
}
