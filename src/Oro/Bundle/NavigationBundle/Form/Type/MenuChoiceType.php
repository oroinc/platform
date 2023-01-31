<?php

namespace Oro\Bundle\NavigationBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\NavigationBundle\Provider\MenuNamesProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Choice form type for menu names.
 */
class MenuChoiceType extends AbstractType
{
    private MenuNamesProvider $menuNamesProvider;

    public function __construct(MenuNamesProvider $menuNamesProvider)
    {
        $this->menuNamesProvider = $menuNamesProvider;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('scope_type');
        $resolver->setAllowedTypes('scope_type', 'string');
        $resolver->setInfo('scope_type', 'Scope type to filter menus by');

        $resolver->setDefault('choices', function (Options $options) {
            $menuNames = $this->menuNamesProvider->getMenuNames($options['scope_type']);

            return array_combine($menuNames, $menuNames);
        });

        $resolver->setDefault('translatable_options', false);
        $resolver->setDefault('multiple', false);
    }

    public function getParent(): string
    {
        return Select2ChoiceType::class;
    }
}
