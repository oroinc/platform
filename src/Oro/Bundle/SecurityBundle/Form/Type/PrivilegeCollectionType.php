<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing a collection of ACL privileges.
 *
 * This form type extends the standard collection type to provide specialized handling
 * for ACL privilege collections, including passing privilege configuration and page
 * component options to child forms for rendering access level selectors.
 */
class PrivilegeCollectionType extends AbstractType
{
    const NAME = 'oro_acl_collection';

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['privileges_config'] = $options['entry_options']['privileges_config'];
        $view->vars['page_component_module'] = $options['page_component_module'];
        $view->vars['page_component_options'] = $options['page_component_options'];
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'page_component_module' => 'orosecurity/js/app/components/security-access-levels-component',
            'page_component_options' => [],
        ]);
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
