<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class ObjectLabelType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_acl_label';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $identity = $view->parent->vars['value']->getId();
        $className = str_replace(['entity:', '\\'], ['', '_'], $identity);

        // add url params for field level aces
        if (strpos($identity, 'entity:') === 0 && $className != ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
            $role = $view->parent->parent->parent->parent->vars['value'];

            $view->vars['roleId'] = $role ? $role->getId() : null;
            $view->vars['className'] = $className;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'hidden';
    }
}
