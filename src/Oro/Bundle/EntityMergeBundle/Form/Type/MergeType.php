<?php

namespace Oro\Bundle\EntityMergeBundle\Form\Type;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;

class MergeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EntityMetadata $metadata */
        $metadata = $options['metadata'];

        $builder->add(
            'masterEntity',
            'entity',
            array(
                'label'                   => 'oro.entity_merge.form.master_record',
                'class'                   => $metadata->getClassName(),
                'choices'                 => $options['entities'],
                'multiple'                => false,
                'tooltip'                 => 'oro.entity_merge.form.master_record.tooltip',
                'expanded'                => true,
                'choices_as_values'       => true,
                'ownership_disabled'      => true,
                'dynamic_fields_disabled' => true,
            )
        );
        $builder->add('fields', 'form');
        $fields = $builder->get('fields');

        foreach ($metadata->getFieldsMetadata() as $fieldMetadata) {
            if ($fieldMetadata->is('display')) {
                $fields->add(
                    $fieldMetadata->getFieldName(),
                    'oro_entity_merge_field',
                    array(
                        'metadata' => $fieldMetadata,
                        'entities' => $options['entities'],
                        'property_path' => sprintf('[%s]', $fieldMetadata->getFieldName()),
                    )
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

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array(
                'metadata',
                'entities',
            )
        );

        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\\Bundle\\EntityMergeBundle\\Data\\EntityData'
            )
        );

        $resolver->setAllowedTypes(
            array(
                'metadata' => 'Oro\\Bundle\\EntityMergeBundle\\Metadata\\EntityMetadata',
                'entities' => 'array',
            )
        );
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
        return 'oro_entity_merge';
    }
}
