<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OroSimpleColorChoiceType extends AbstractSimpleColorPickerType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults(
                [
                    'choices' => []
                ]
            )
            ->setNormalizer(
                'choices',
                function (Options $options, $choices) {
                    return $options['color_schema'] === 'custom'
                        ? $choices
                        : $this->getColors($options['color_schema']);
                }
            );
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        FormUtils::appendClass($view, 'no-input-widget');
        $view->vars['translatable']      = $options['translatable'];
        $view->vars['allow_empty_color'] = $options['allow_empty_color'];
        $view->vars['empty_color']       = $options['empty_color'];
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_simple_color_choice';
    }
}
