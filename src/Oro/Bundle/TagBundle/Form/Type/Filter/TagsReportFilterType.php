<?php

namespace Oro\Bundle\TagBundle\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form type for filtering tags in reports with entity class specification.
 *
 * This filter type extends the dictionary filter to provide tag filtering capabilities in report contexts.
 * It adds an entity_class field to allow filtering tags for specific entity types, enabling flexible
 * tag-based reporting across different taggable entities.
 */
class TagsReportFilterType extends DictionaryFilterType
{
    public const NAME = 'oro_type_tags_report_filter';

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
