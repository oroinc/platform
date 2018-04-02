<?php

namespace Oro\Bundle\FilterBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateRangeType extends AbstractType
{
    const NAME = 'oro_type_date_range';

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @param LocaleSettings $localeSettings
     */
    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritDoc}
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
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'start',
            $options['field_type'],
            array_merge(
                array(
                    'required'       => false,
                    'widget'         => 'single_text',
                    'format'         => 'yyyy-MM-dd',
                    'model_timezone' => $this->localeSettings->getTimeZone(),
                    'view_timezone'  => $this->localeSettings->getTimeZone(),
                ),
                $options['field_options'],
                $options['start_field_options']
            )
        );

        $builder->add(
            'end',
            $options['field_type'],
            array_merge(
                array(
                    'required'       => false,
                    'widget'         => 'single_text',
                    'format'         => 'yyyy-MM-dd',
                    'model_timezone' => $this->localeSettings->getTimeZone(),
                    'view_timezone'  => $this->localeSettings->getTimeZone(),
                ),
                $options['field_options'],
                $options['end_field_options']
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $children                     = $form->all();
        $view->vars['value']['start'] = $children['start']->getViewData();
        $view->vars['value']['end']   = $children['end']->getViewData();
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'field_type'          => DateType::class,
                'field_options'       => array(),
                'start_field_options' => array(),
                'end_field_options'   => array(),
            )
        );
    }
}
