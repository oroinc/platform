<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ShareType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('entityClass', 'hidden', ['required' => false])
            ->add('entityId', 'hidden', ['required' => false])
            ->add(
                'entities',
                'oro_share_select',
                [
                    'label' => 'oro.security.action.share_with',
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'Oro\Bundle\SecurityBundle\Form\Model\Share',
                'intention'          => 'entities',
                'csrf_protection'    => false,
                'cascade_validation' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_share';
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['entityId'] = $form->get('entityId')->getData();
        $view->vars['entityClass'] = $form->get('entityClass')->getData();

        $routeParameters = isset($view->children['entities']->vars['configs']['route_parameters'])
            ? $view->children['entities']->vars['configs']['route_parameters']
            : [];
        $routeParameters['entityClass'] = $form->get('entityClass')->getData();

        $view->children['entities']->vars['configs']['route_parameters'] = $routeParameters;
    }
}
