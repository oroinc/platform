<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class PermissionCollectionType extends AbstractType
{
    const NAME = 'oro_acl_permission_collection';

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['privileges_config'] = $options['options']['privileges_config'];

        if ($key = array_search('SHARE', $view->vars['privileges_config']['permissions'], true)) {
            unset($view->vars['privileges_config']['permissions'][$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'collection';
    }
}
