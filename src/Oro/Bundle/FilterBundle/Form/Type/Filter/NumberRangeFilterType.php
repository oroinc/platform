<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class NumberRangeFilterType extends AbstractType implements NumberRangeFilterTypeInterface
{
    const NAME = 'oro_type_number_range_filter';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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
    public function getParent()
    {
        return NumberFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('value_end', HiddenType::class) // range end
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $operatorChoices = [
            $this->translator->trans('oro.filter.form.label_type_range_between') => self::TYPE_BETWEEN,
            $this->translator->trans('oro.filter.form.label_type_range_not_between') => self::TYPE_NOT_BETWEEN,
            $this->translator->trans('oro.filter.form.label_type_range_equals') => self::TYPE_EQUAL,
            $this->translator->trans('oro.filter.form.label_type_range_not_equals') => self::TYPE_NOT_EQUAL,
            $this->translator->trans('oro.filter.form.label_type_range_more_than') => self::TYPE_GREATER_THAN,
            $this->translator->trans('oro.filter.form.label_type_range_less_than') => self::TYPE_LESS_THAN,
            $this->translator->trans('oro.filter.form.label_type_range_more_equals') => self::TYPE_GREATER_EQUAL,
            $this->translator->trans('oro.filter.form.label_type_range_less_equals') => self::TYPE_LESS_EQUAL,
            $this->translator->trans('oro.filter.form.label_type_empty') => FilterUtility::TYPE_EMPTY,
            $this->translator->trans('oro.filter.form.label_type_not_empty') => FilterUtility::TYPE_NOT_EMPTY,
        ];

        $resolver->setDefaults([
            'operator_choices' => $operatorChoices,
        ]);
    }
}
