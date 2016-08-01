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
        // @todo remove 'isPath', 'class' options after BAP-4696 implementation
        $resolver
            ->setRequired(['route', 'acl', 'title'])
            ->setOptional([
                'routeParameters',
                'isPath',
                'class'
            ])
            ->setDefaults([
                'routeParameters' => [],
                'isPath'          => false,
                'class'           => ''
            ])
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
        // @todo remove 'isPath', 'class' options after BAP-4696 implementation
        $view->vars['isPath']          = $options['isPath'];
        $view->vars['class']           = $options['class'];
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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_link_type';
    }
}
