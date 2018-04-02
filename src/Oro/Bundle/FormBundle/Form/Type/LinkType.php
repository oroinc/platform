<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LinkType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // @todo remove 'isPath', 'class' options after BAP-4696 implementation
        $resolver
            ->setRequired(['route', 'acl', 'title'])
            ->setDefined([
                'routeParameters',
                'isPath',
                'class'
            ])
            ->setDefaults([
                'routeParameters' => [],
                'isPath'          => false,
                'class'           => ''
            ])
            ->setAllowedTypes('route', 'string')
            ->setAllowedTypes('acl', 'string')
            ->setAllowedTypes('title', 'string');
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
        return TextType::class;
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
