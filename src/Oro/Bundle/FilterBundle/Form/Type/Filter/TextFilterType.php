<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type for text filters.
 *
 * This form type provides a comprehensive filter interface for text-based filtering.
 * It supports multiple operators including `contains`, `not contains`, `equals`,
 * `starts with`, `ends with`, `in`, `not in`, `empty`, and `not empty`, allowing
 * users to filter records based on various text matching criteria. The type requires
 * a translator service for localizing operator labels.
 */
class TextFilterType extends AbstractType
{
    public const TYPE_CONTAINS     = 1;
    public const TYPE_NOT_CONTAINS = 2;
    public const TYPE_EQUAL        = 3;
    public const TYPE_STARTS_WITH  = 4;
    public const TYPE_ENDS_WITH    = 5;
    public const TYPE_IN           = 6;
    public const TYPE_NOT_IN       = 7;
    public const NAME              = 'oro_type_text_filter';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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
        return FilterType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [
            $this->translator->trans('oro.filter.form.label_type_contains') => self::TYPE_CONTAINS,
            $this->translator->trans('oro.filter.form.label_type_not_contains') => self::TYPE_NOT_CONTAINS,
            $this->translator->trans('oro.filter.form.label_type_equals') => self::TYPE_EQUAL,
            $this->translator->trans('oro.filter.form.label_type_start_with') => self::TYPE_STARTS_WITH,
            $this->translator->trans('oro.filter.form.label_type_end_with') => self::TYPE_ENDS_WITH,
            $this->translator->trans('oro.filter.form.label_type_in') => self::TYPE_IN,
            $this->translator->trans('oro.filter.form.label_type_not_in') => self::TYPE_NOT_IN,
            $this->translator->trans('oro.filter.form.label_type_empty') => FilterUtility::TYPE_EMPTY,
            $this->translator->trans('oro.filter.form.label_type_not_empty') => FilterUtility::TYPE_NOT_EMPTY,
        ];

        $resolver->setDefaults(
            [
                'field_type'       => TextType::class,
                'operator_choices' => $choices,
            ]
        );
    }
}
