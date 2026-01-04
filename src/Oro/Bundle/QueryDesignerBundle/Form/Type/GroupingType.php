<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityFieldSelectType;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Grouping field choice form type.
 */
class GroupingType extends AbstractType
{
    public const NAME = 'oro_query_designer_grouping';

    /** @var Manager */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options = [
            'required'            => true,
            'page_component_name' => 'grouping-field-choice',
            'page_component_options' => [
                'view'         => 'oroquerydesigner/js/app/views/grouping-field-choice-view'
            ],
        ];

        $metadata = $this->manager->getMetadataForGrouping();
        if (isset($metadata['include'])) {
            $options['include_fields'] = $metadata['include'];
        }
        if (isset($metadata['exclude'])) {
            $options['exclude_fields'] = $metadata['exclude'];
        }

        $builder
            ->add('columnNames', FieldChoiceType::class, $options);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'entity'             => null,
                'data_class'         => 'Oro\Bundle\QueryDesignerBundle\Model\Grouping',
                'csrf_token_id'      => 'query_designer_grouping',
                'column_choice_type' => EntityFieldSelectType::class
            )
        );
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
}
