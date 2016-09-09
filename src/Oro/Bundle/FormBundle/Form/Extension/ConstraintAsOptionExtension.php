<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;

class ConstraintAsOptionExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;
    
    /**
     * @var ConstraintFactory
     */
    protected $constraintFactory;

    /**
     * @param ConstraintFactory $constraintFactory
     */
    public function __construct(ConstraintFactory $constraintFactory)
    {
        $this->constraintFactory = $constraintFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setNormalizers(
            [
                'constraints' => function (Options $options, $constraints) {
                    return $this->constraintFactory->parse(
                        is_object($constraints) ? [$constraints] : (array) $constraints
                    );
                }
            ]
        );
    }
}
