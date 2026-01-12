<?php

namespace Oro\Bundle\EntityMergeBundle\Form\Type;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Builds the main form for merging multiple entities into a single master entity.
 *
 * Constructs a form that allows users to select a master entity and configure how each
 * field should be merged from the source entities. It dynamically adds merge field forms
 * for all displayable fields in the entity metadata, and automatically defaults the master
 * entity to the first entity in the list if not explicitly set.
 */
class MergeType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EntityMetadata $metadata */
        $metadata = $options['metadata'];

        $builder->add(
            'masterEntity',
            EntityType::class,
            [
                'label'                   => 'oro.entity_merge.form.master_record',
                'class'                   => $metadata->getClassName(),
                'choices'                 => $options['entities'],
                'multiple'                => false,
                'tooltip'                 => 'oro.entity_merge.form.master_record.tooltip',
                'expanded'                => true,
                'ownership_disabled'      => true,
                'dynamic_fields_disabled' => true,
            ]
        );
        $builder->add('fields', FormType::class);
        $fields = $builder->get('fields');

        foreach ($metadata->getFieldsMetadata() as $fieldMetadata) {
            if ($fieldMetadata->is('display')) {
                $fields->add(
                    $fieldMetadata->getFieldName(),
                    MergeFieldType::class,
                    [
                        'metadata' => $fieldMetadata,
                        'entities' => $options['entities'],
                        'property_path' => sprintf('[%s]', $fieldMetadata->getFieldName()),
                    ]
                );
            }
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var EntityData $entityData */
            $entityData = $event->getData();
            if (!$entityData->getMasterEntity()) {
                $entities = $entityData->getEntities();
                $masterEntity = reset($entities);
                $entityData->setMasterEntity($masterEntity);
            }
        });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'metadata',
                'entities',
            ]
        );

        $resolver->setDefaults(
            [
                'data_class' => 'Oro\\Bundle\\EntityMergeBundle\\Data\\EntityData'
            ]
        );

        $resolver->setAllowedTypes('metadata', 'Oro\\Bundle\\EntityMergeBundle\\Metadata\\EntityMetadata');
        $resolver->setAllowedTypes('entities', 'array');
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_merge';
    }
}
