<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizationParentSelectType extends AbstractType
{
    const NAME = 'oro_localization_parent_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_localization_parent',
                'configs' => [
                    'component' => 'autocomplete-entity-parent',
                    'placeholder' => 'oro.locale.localization.form.placeholder.select_parent_localization'
                ],
                'grid_name' => 'oro-locale-localizations-select-grid',
            ]
        );
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $parentData = $form->getParent()->getData();

        $view->vars['configs']['entityId'] = $parentData instanceof Localization ? $parentData->getId() : null;
        $view->vars['grid_parameters'] = [
            'ids' => $parentData instanceof Localization ? $parentData->getChildrenIds(true) : []
        ];
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroEntitySelectOrCreateInlineType::class;
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
}
