<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class UniqueKeyCollectionType extends AbstractType
{
    /**
     * @var ConfigProvider
     */
    protected $extendProvider;

    /**
     * @var ConfigProvider
     */
    protected $entityProvider;

    /**
     * @param ConfigProvider $extendProvider
     * @param ConfigProvider $entityProvider
     */
    public function __construct(ConfigProvider $extendProvider, ConfigProvider $entityProvider)
    {
        $this->extendProvider = $extendProvider;
        $this->entityProvider = $entityProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $className = $options['className'];

        $fieldConfigIds = $this->extendProvider->getIds($className);

        $fields = array_filter(
            $fieldConfigIds,
            function (FieldConfigId $fieldConfigId) {
                return $fieldConfigId->getFieldType() != 'ref-many';
            }
        );

        $builder->add(
            'keys',
            'collection',
            array(
                'required'       => true,
                'type'           => new UniqueKeyType($fields),
                'allow_add'      => true,
                'allow_delete'   => true,
                'prototype'      => true,
                'prototype_name' => 'tag__name__',
                'label'          => ' '
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['className'])
            ->setAllowedTypes(['className' => 'string']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_unique_key_collection_type';
    }
}
