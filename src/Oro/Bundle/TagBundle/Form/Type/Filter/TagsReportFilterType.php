<?php

namespace Oro\Bundle\TagBundle\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TagsReportFilterType extends DictionaryFilterType
{
    const NAME = 'oro_type_tags_report_filter';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('entity_class', TextType::class, ['required' => true]);
    }

    #[\Override]
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
