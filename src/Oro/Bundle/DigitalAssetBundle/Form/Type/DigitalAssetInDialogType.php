<?php

namespace Oro\Bundle\DigitalAssetBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileConstraintFromSystemConfig;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form type for DigitalAsset
 * - used in digital asset manager dialog window
 */
class DigitalAssetInDialogType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'titles',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.digitalasset.titles.label',
                    'tooltip' => $options['is_image_type']
                        ? 'oro.digitalasset.titles.tooltip_image'
                        : 'oro.digitalasset.titles.tooltip_file',
                    'required' => true,
                    'entry_options' => [
                        'constraints' => [
                            new NotBlank(),
                            new Length(['max' => 255])
                        ]
                    ],
                ]
            )
            ->add(
                'sourceFile',
                FileType::class,
                [
                    'label' => $options['is_image_type']
                        ? 'oro.digitalasset.dam.dialog.image.label'
                        : 'oro.digitalasset.dam.dialog.file.label',
                    'required' => true,
                    'allowDelete' => false,
                    'addEventSubscriber' => false,
                    'fileOptions' => [
                        'required' => true,
                        'constraints' => [
                            new NotBlank(),
                            new FileConstraintFromSystemConfig([
                                'mimeTypes' => $options['mime_types'],
                                'maxSize' => $options['max_file_size']
                            ]),
                        ],
                    ],
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'is_image_type' => false,
                'mime_types' => [],
                'max_file_size' => 0,
                'data_class' => DigitalAsset::class,
                'validation_groups' => ['Default', 'DigitalAssetInDialog'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_digital_asset_in_dialog';
    }
}
