<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueKeys;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType as RelationTypeBase;

class UniqueKeyCollectionType extends AbstractType
{
    /**
     * @var ConfigProvider
     */
    protected $entityProvider;

    /**
     * @param ConfigProvider $entityProvider
     */
    public function __construct(ConfigProvider $entityProvider)
    {
        $this->entityProvider = $entityProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $fields         = [];
        $className      = $options['className'];
        $fieldConfigIds = $this->entityProvider->getIds($className);

        /** @var FieldConfigId $fieldConfigId */
        foreach ($fieldConfigIds as $fieldConfigId) {
            if ($fieldConfigId->getFieldType() === RelationTypeBase::TO_MANY) {
                continue;
            }

            $fieldName = $fieldConfigId->getFieldName();

            $fields[$fieldName] = $this
                ->entityProvider
                ->getConfig($className, $fieldName)
                ->get('label', false, ucfirst($fieldName));
        }

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
                'label'          => false,
                'constraints'    => [new UniqueKeys()]
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
