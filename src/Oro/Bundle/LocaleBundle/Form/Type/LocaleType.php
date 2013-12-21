<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\ChoicesToBooleanArrayTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToStringTransformer;

class LocaleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['multiple']) {
            $builder
                ->addModelTransformer(new ReversedTransformer(new ArrayToStringTransformer(',', true)))
                ->addEventListener(
                    FormEvents::PRE_SET_DATA,
                    function (FormEvent $event) {
                        $value = $event->getData();
                        if (is_array($value)) {
                            $event->setData(implode(',', $value));
                        }
                    }
                );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices' => Intl::getLocaleBundle()->getLocaleNames('en'),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'locale';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_locale';
    }
}
