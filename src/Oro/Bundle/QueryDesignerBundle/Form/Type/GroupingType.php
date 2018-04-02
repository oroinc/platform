<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityFieldSelectType;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GroupingType extends AbstractType
{
    const NAME = 'oro_query_designer_grouping';

    /** @var Manager */
    protected $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options = [
            'required'            => true,
            'page_component_name' => 'grouping-field-choice',
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
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
