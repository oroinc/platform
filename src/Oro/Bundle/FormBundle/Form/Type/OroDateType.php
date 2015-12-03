<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterRegistry;
use Oro\Bundle\LocaleBundle\Converter\IntlDateTimeFormatConverter;
use Oro\Bundle\UIBundle\Converter\JqueryUiDateTimeFormatConverter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class OroDateType extends AbstractType
{
    const NAME = 'oro_date';

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['placeholder'] = $options['placeholder'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'model_timezone' => 'UTC',
                'view_timezone'  => 'UTC',
                'format'         => 'yyyy-MM-dd', // ISO format
                'widget'         => 'single_text',
                'placeholder'    => 'oro.form.click_here_to_select',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'date';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
