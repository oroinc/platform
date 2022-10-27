<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;

/**
 * Converts constraints definitions specified in "constrains" option to {@see Constraint} objects.
 */
class ConstraintAsOptionExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    /**
     * @var ConstraintFactory
     */
    protected $constraintFactory;

    public function __construct(ConstraintFactory $constraintFactory)
    {
        $this->constraintFactory = $constraintFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setAllowedTypes(
            'constraints',
            [Constraint::class, Constraint::class . '[]', 'array', 'string', 'null']
        );

        $resolver->setNormalizer(
            'constraints',
            function (Options $options, $constraints) {
                return $this->constraintFactory->parse(
                    is_object($constraints) ? [$constraints] : (array)$constraints
                );
            }
        );
    }
}
