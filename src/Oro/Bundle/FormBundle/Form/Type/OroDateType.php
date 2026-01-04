<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for date, extends Symfony DateType:
 * - adds ability to set min and max date
 * - fixes buggy 'placeholder' normalizer
 */
class OroDateType extends AbstractType
{
    public const NAME = 'oro_date';

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!empty($options['placeholder'])) {
            $view->vars['attr']['placeholder'] = $options['placeholder'];
        }

        // jquery date/datetime pickers support only year ranges
        if (!empty($options['years'])) {
            $view->vars['years'] = sprintf('%d:%d', min($options['years']), max($options['years']));
        }

        $view->vars['minDate'] = $options['minDate'];
        $view->vars['maxDate'] = $options['maxDate'];
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'model_timezone' => 'UTC',
                'view_timezone'  => 'UTC',
                'format'         => DateType::HTML5_FORMAT,
                'widget'         => 'single_text',
                'placeholder'    => 'oro.form.click_here_to_select',
                'years'          => [],
                'minDate'        => null,
                'maxDate'        => null,
            ]
        );

        // remove buggy 'placeholder' normalizer. The placeholder must be a string if 'widget' === 'single_text'
        $resolver->setNormalizer(
            'placeholder',
            function (Options $options, $placeholder) {
                return $placeholder;
            }
        );

        $resolver->setAllowedTypes('minDate', ['string', 'null']);
        $resolver->setAllowedTypes('maxDate', ['string', 'null']);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return DateType::class;
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
