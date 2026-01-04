<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class WidgetItemType extends AbstractType
{
    public const NAME = 'oro_type_widget_item';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('order', HiddenType::class)
            ->add('show', CheckboxType::class, [
                'data' => false,
            ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
