<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WidgetTitleType extends AbstractType
{
    const NAME = 'oro_type_widget_title';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'title',
            'text',
            [
                'required' => false
            ]
        )->add(
            'useDefault',
            'checkbox',
            [
                'label' => 'oro.dashboard.title.use_default.label',
                'required' => false,
                'attr' => [
                    'checked' => 'checked'
                ]
            ]
        );
    }
}
