<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

use Symfony\Component\Form\FormBuilderInterface;

class UniqueKeyCollectionType extends AbstractType
{
    /**
     * @var FieldConfigId[]
     */
    protected $fields;

    public function __construct($fields)
    {
        $this->fields = $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'keys',
            CollectionType::class,
            array(
                'required'       => true,
                'type'           => new UniqueKeyType($this->fields),
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
    public function getBlockPrefix()
    {
        return 'oro_entity_extend_unique_key_collection_type';
    }
}
