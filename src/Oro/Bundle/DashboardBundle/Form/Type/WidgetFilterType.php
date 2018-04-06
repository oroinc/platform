<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToJsonTransformer;
use Oro\Bundle\QueryDesignerBundle\Form\Type\FilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WidgetFilterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('entity', HiddenType::class, ['data' => $options['entity']]);
        $builder->add('definition', HiddenType::class, ['required' => false]);
        $factory = $builder->getFormFactory();
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($factory) {
                $form = $event->getForm();
                $data = $event->getData();
                $entity = $data ? $data['entity'] : null;
                $filterOptions = [
                    'mapped'             => false,
                    'column_choice_type' => null,
                    'entity'             => $entity,
                    'auto_initialize'    => false
                ];
                $form->add(
                    $factory->createNamed('filter', FilterType::class, null, $filterOptions)
                );
            }
        );
        $builder->get('definition')
            ->addViewTransformer(new ArrayToJsonTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['widgetType']  = $options['widgetType'];
        $view->vars['collapsible'] = $options['collapsible'];
        $view->vars['collapsed']   = $this->resolveCollapsed($view, $options);
        parent::buildView($view, $form, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'widgetType'    => null,
                'entity'        => null,
                'collapsible'   => false,
                'collapsed'     => false,
                'expand_filled' => false
            ]
        );
    }

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
        return 'oro_dashboard_query_filter';
    }

    /**
     * @param FormView $view
     * @param array    $options
     *
     * @return bool
     */
    protected function resolveCollapsed(FormView $view, array $options)
    {
        if ($options['collapsed']) {
            return true;
        }
        if (!$options['expand_filled']) {
            return $options['collapsed'];
        }

        $val = $view->vars['value'];

        return empty($val['definition']['filters']);
    }
}
