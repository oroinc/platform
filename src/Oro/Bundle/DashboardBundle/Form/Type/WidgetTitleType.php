<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class WidgetTitleType extends AbstractType
{
    const NAME = 'oro_type_widget_title';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'title',
            'text',
            [
                'required' => false
            ]
        );
        $builder->add(
            'useDefault',
            'checkbox',
            [
                'label'      => 'oro.dashboard.title.use_default.label',
                'required'   => false
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!isset($view->vars['value'], $view->vars['value']['useDefault'])) {
            $form->get('useDefault')->setData(true);
        }
    }
}
