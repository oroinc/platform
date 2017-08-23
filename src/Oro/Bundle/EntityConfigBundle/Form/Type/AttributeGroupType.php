<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AttributeGroupType extends AbstractType
{
    const NAME = 'oro_entity_config_attribute_group';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'attr' => [
                    'data-page-component-module' => 'oroentityconfig/js/attribute-group-collection-component',
                    'data-attribute-group' => true
                ],
            ]
        );

        $resolver->setRequired(['attributeEntityClass']);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'labels',
            LocalizedFallbackValueCollectionType::NAME,
            [
                'label' => 'oro.entity_config.attribute_group.labels.label',
                'required' => true,
                'options' => [
                    'constraints' => [
                        new NotBlank(['message' => 'oro.entity_config.validator.attribute_family.labels.blank'])
                    ],
                    'attr' => [
                        'data-attribute-select-group' => true
                    ]
                ],
            ]
        );

        $builder->add(
            'isVisible',
            CheckboxType::class,
            [
                'label' => 'oro.entity_config.attribute_group.is_visible.label'
            ]
        );

        $builder->add(//This needed for new forms which will be dynamically added
            'attributeRelations',
            AttributeMultiSelectType::NAME,
            [
                'label' => 'oro.entity_config.attribute_group.attribute_relations.label',
                'configs' => [
                    'component' => 'attribute-autocomplete',
                ],
                'attributeEntityClass' => $options['attributeEntityClass'],
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData']);
    }

    /**
     * @param FormEvent $event
     */
    public function postSetData(FormEvent $event)
    {
        /** @var AttributeGroup $data */
        $data = $event->getData();

        if (!$data) {
            return;
        }

        $form = $event->getForm();
        $form->add(
            'attributeRelations',
            AttributeMultiSelectType::NAME,
            [
                'label' => 'oro.entity_config.attribute_group.attribute_relations.label',
                'configs' => [
                    'component' => 'attribute-autocomplete',
                ],
                'attributeGroup' => $data
            ]
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
        return self::NAME;
    }
}
