<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class SearchStringFilterType extends AbstractType
{
    const NAME = 'oro_search_type_string_filter';

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
    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = [
            $this->translator->trans('oro.filter.form.label_type_contains') => TextFilterType::TYPE_CONTAINS,
            $this->translator->trans('oro.filter.form.label_type_not_contains') => TextFilterType::TYPE_NOT_CONTAINS,
            $this->translator->trans('oro.filter.form.label_type_equals') => TextFilterType::TYPE_EQUAL,
        ];

        $resolver->setDefaults(['operator_choices' => $choices]);
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
        return TextFilterType::class;
    }
}
