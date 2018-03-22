<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType as BaseTextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AttributeFamilyType extends AbstractType
{
    const NAME = 'oro_attribute_family';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'code',
            BaseTextType::class,
            [
                'label' => 'oro.entity_config.attribute_family.code.label',
                'block' => 'settings',
            ]
        );

        $builder->add(
            'labels',
            LocalizedFallbackValueCollectionType::class,
            [
                'label' => 'oro.entity_config.attribute_family.labels.label',
                'block' => 'settings',
                'required' => true,
                'entry_options' => [
                    'constraints' => [
                        new NotBlank(['message' => 'oro.entity_config.validator.attribute_family.labels.blank'])
                    ]
                ],
            ]
        );

        $builder->add(
            'isEnabled',
            CheckboxType::class,
            [
                'label' => 'oro.entity_config.attribute_family.enabled.label',
                'block' => 'settings'
            ]
        );

        $builder->add(
            'image',
            ImageType::class,
            [
                'label' => 'oro.entity_config.attribute_family.image.label',
                'block' => 'settings',
                'required' => false
            ]
        );

        $builder->add(
            'attributeGroups',
            AttributeGroupCollectionType::class,
            [
                'label' => 'oro.entity_config.attribute_family.attribute_groups.label',
                'block' => 'attributes',
                'required' => false,
                'entry_options' => [
                    'attributeEntityClass' => $options['attributeEntityClass'],
                    'data_class' => AttributeGroup::class
                ],
                'prototype_data' => new AttributeGroup()
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AttributeFamily::class,
            'block_config' => [
                'settings' => [
                    'title' =>
                        $this->translator->trans('oro.entity_config.sections.attribute_family.settings'),
                ]
            ]
        ]);

        $resolver->setRequired(['attributeEntityClass']);
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
