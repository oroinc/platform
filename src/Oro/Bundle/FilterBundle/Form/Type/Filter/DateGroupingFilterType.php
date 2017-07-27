<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DateGroupingFilterType extends AbstractChoiceType
{
    const NAME = 'oro_type_date_grouping_filter';
    const TYPE_DAY = 'day';
    const TYPE_MONTH = 'month';
    const TYPE_QUARTER = 'quarter';
    const TYPE_YEAR = 'year';

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
    public function getParent()
    {
        return ChoiceFilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            if (array_key_exists('value', $data) && !$data['value']) {
                $event->setData(self::TYPE_DAY);
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'field_type'       => 'choice',
                'field_options'    => [
                    'choices' => $this->getGroupChoices(),
                ],
                'default_value'    => ucfirst(self::TYPE_DAY),
            )
        );
    }

    /**
     * @return array
     */
    protected function getGroupChoices()
    {
        return [
            self::TYPE_DAY => ucfirst(self::TYPE_DAY),
            self::TYPE_MONTH => ucfirst(self::TYPE_MONTH),
            self::TYPE_QUARTER => ucfirst(self::TYPE_QUARTER),
            self::TYPE_YEAR => ucfirst(self::TYPE_YEAR),
        ];
    }
}
