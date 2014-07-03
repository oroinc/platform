<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LinkType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['route', 'acl', 'title'])
            ->setOptional(['routeParameters'])
            ->setDefaults(['routeParameters' => []])
            ->setAllowedTypes(
                [
                    'route' => 'string',
                    'acl'   => 'string',
                    'title' => 'string',
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['route']           = $options['route'];
        $view->vars['acl']             = $options['acl'];
        $view->vars['title']           = $options['title'];
        $view->vars['routeParameters'] = $options['routeParameters'];
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
    public function getName()
    {
        return 'oro_link_type';
    }
}
