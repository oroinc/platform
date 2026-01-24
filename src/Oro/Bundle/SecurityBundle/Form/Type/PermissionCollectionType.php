<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Form type for managing a collection of ACL permissions.
 *
 * This form type extends the standard collection type to provide specialized handling
 * for ACL permission collections, including passing privilege configuration to child forms.
 */
class PermissionCollectionType extends AbstractType
{
    const NAME = 'oro_acl_permission_collection';

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['privileges_config'] = $options['entry_options']['privileges_config'];
    }

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
    public function getParent(): ?string
    {
        return CollectionType::class;
    }
}
