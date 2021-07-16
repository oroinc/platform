<?php

namespace Oro\Bundle\FilterBundle\Form\Type;

use Oro\Bundle\FilterBundle\Filter\DateRangeFilter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for a value of {@see \Oro\Bundle\FilterBundle\Filter\DateRangeFilter}.
 */
class DateRangeType extends AbstractType
{
    const NAME = 'oro_type_date_range';

    /** @var LocaleSettings */
    private $localeSettings;

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
        $this->addDateField($builder, 'start', $options);
        $this->addDateField($builder, 'end', $options);
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $children = $form->all();
        $view->vars['value']['start'] = $children['start']->getViewData();
        $view->vars['value']['end'] = $children['end']->getViewData();
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'field_type'          => DateType::class,
            'field_options'       => [],
            'start_field_options' => [
                'html5' => false,
            ],
            'end_field_options'   => [
                'html5' => false,
            ]
        ]);
    }

    private function addDateField(FormBuilderInterface $builder, string $name, array $parentOptions): void
    {
        $builder->add(
            $name,
            $parentOptions['field_type'],
            array_merge(
                [
                    'required'       => false,
                    'widget'         => 'single_text',
                    'format'         => DateRangeFilter::DATE_FORMAT,
                    'model_timezone' => $this->localeSettings->getTimeZone(),
                    'view_timezone'  => $this->localeSettings->getTimeZone(),
                ],
                $parentOptions['field_options'],
                $parentOptions[$name . '_field_options']
            )
        );
    }
}
