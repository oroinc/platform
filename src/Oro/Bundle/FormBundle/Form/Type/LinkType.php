<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for a link.
 */
class LinkType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['route', 'title'])
            ->setDefined(['acl', 'routeParameters'])
            ->setDefaults(['acl' => null, 'routeParameters' => []])
            ->setAllowedTypes('route', 'string')
            ->setAllowedTypes('acl', ['string', 'null'])
            ->setAllowedTypes('title', 'string');
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['title'] = $options['title'];
        $view->vars['route'] = $options['route'];
        $view->vars['routeParameters'] = $options['routeParameters'];
        $view->vars['acl'] = $options['acl'];
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
