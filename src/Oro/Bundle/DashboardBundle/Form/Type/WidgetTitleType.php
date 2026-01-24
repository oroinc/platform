<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Form type for configuring widget title with optional default value.
 *
 * This form type provides a title input field along with a "Use Default" checkbox,
 * allowing users to either specify a custom title for a widget or use the default
 * title defined in the widget configuration. The form automatically manages the
 * relationship between the checkbox state and the title field, ensuring proper
 * default value handling.
 */
class WidgetTitleType extends AbstractType
{
    const NAME = 'oro_type_widget_title';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'title',
            TextType::class,
            [
                'required' => false
            ]
        );
        $builder->add(
            'useDefault',
            CheckboxType::class,
            [
                'label'      => 'oro.dashboard.title.use_default.label',
                'required'   => false
            ]
        );
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!isset($view->vars['value'], $view->vars['value']['useDefault'])) {
            $form->get('useDefault')->setData(true);
        }
    }
}
