<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WidgetDateRangeItemType extends AbstractType
{
    const NAME = 'oro_type_widget_date_range_item';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'pattern'        => 'yyyy-MM-dd',
            'model_timezone' => 'UTC',
            'view_timezone'  => 'UTC',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new DateTimeToLocalizedStringTransformer(
            $options['model_timezone'],
            $options['view_timezone'],
            null,
            null,
            null,
            $options['pattern']
        ));
        $builder->addModelTransformer(new ReversedTransformer(
            new DateTimeToStringTransformer($options['model_timezone'], $options['model_timezone'], 'Y-m-d')
        ));
    }
}
