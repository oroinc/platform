<?php

namespace Oro\Bundle\ApiBundle\Form\Extension;

use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Unlike default Symfony Forms behaviour, keeps NULL and empty string values as is.
 * Also see the related changes:
 * @see \Oro\Bundle\ApiBundle\Form\DataTransformer\NullValueTransformer
 */
class EmptyDataExtension extends AbstractTypeExtension
{
    /** @var EntityInstantiator */
    protected $entityInstantiator;

    /**
     * @param EntityInstantiator $entityInstantiator
     */
    public function __construct(EntityInstantiator $entityInstantiator)
    {
        $this->entityInstantiator = $entityInstantiator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'empty_data' => function (Options $options) {
                $className = $options['data_class'];

                if (null !== $className) {
                    return function (FormInterface $form) use ($className) {
                        return $form->isEmpty() && !$form->isRequired()
                            ? null
                            : $this->entityInstantiator->instantiate($className);
                    };
                }

                return function (FormInterface $form, $value) {
                    return $form->getConfig()->getCompound()
                        ? []
                        : $value;
                };
            }
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FormType::class;
    }
}
