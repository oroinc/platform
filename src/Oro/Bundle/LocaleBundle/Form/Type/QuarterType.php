<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToArrayTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuarterType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->remove('year');

        FormUtils::replaceTransformer(
            $builder,
            new DateTimeToArrayTransformer($options['model_timezone'], $options['view_timezone'], ['month', 'day']),
            'view'
        );
        FormUtils::replaceTransformer(
            $builder,
            new ReversedTransformer(
                new DateTimeToArrayTransformer($options['model_timezone'], $options['model_timezone'], ['month', 'day'])
            )
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'model_timezone' => 'UTC',
                'view_timezone'  => 'UTC',
                'format'         => 'dMMMy',
                'input'          => 'array',
            ]
        );

        $resolver->setAllowedValues('input', ['array']);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_quarter';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return DateType::class;
    }
}
