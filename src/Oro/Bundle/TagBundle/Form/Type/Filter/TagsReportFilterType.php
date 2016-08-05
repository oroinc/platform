<?php

namespace Oro\Bundle\TagBundle\Form\Type\Filter;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;

class TagsReportFilterType extends DictionaryFilterType
{
    const NAME = 'oro_type_tags_report_filter';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('entity_class', 'text', ['required' => true]);
    }

    /**
     * {@inheritdoc}
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
}
